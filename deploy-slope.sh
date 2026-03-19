#!/bin/bash

# Script di Deployment Sistema Sincronizzazione Slope
# Uso: ./deploy-slope.sh [--skip-backup] [--dry-run]

set -e  # Exit on error

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variabili
SKIP_BACKUP=false
DRY_RUN=false
BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Parse arguments
for arg in "$@"; do
    case $arg in
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --help)
            echo "Uso: ./deploy-slope.sh [opzioni]"
            echo ""
            echo "Opzioni:"
            echo "  --skip-backup    Salta il backup del database"
            echo "  --dry-run        Esegue solo test senza modificare il database"
            echo "  --help           Mostra questo messaggio"
            exit 0
            ;;
    esac
done

# Funzioni helper
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Verifica che siamo nella directory corretta
if [ ! -f "artisan" ]; then
    print_error "Errore: artisan non trovato. Esegui questo script dalla root del progetto Laravel."
    exit 1
fi

print_header "DEPLOYMENT SISTEMA SINCRONIZZAZIONE SLOPE"

# 1. Backup Database
if [ "$SKIP_BACKUP" = false ]; then
    print_header "1. Backup Database"
    
    # Crea directory backup se non esiste
    mkdir -p "$BACKUP_DIR"
    
    # Leggi credenziali database da .env
    DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    
    BACKUP_FILE="$BACKUP_DIR/slope_backup_$TIMESTAMP.sql"
    
    print_info "Creazione backup: $BACKUP_FILE"
    
    if mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null; then
        print_success "Backup completato: $BACKUP_FILE"
    else
        print_error "Backup fallito. Continuare comunque? (y/n)"
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
else
    print_warning "Backup saltato (--skip-backup)"
fi

# 2. Verifica Composer Dependencies
print_header "2. Verifica Dependencies"
print_info "Verifica composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader
print_success "Dependencies verificate"

# 3. Clear Cache
print_header "3. Clear Cache"
php artisan config:clear
php artisan cache:clear
print_success "Cache pulita"

# 4. Migration
if [ "$DRY_RUN" = false ]; then
    print_header "4. Esecuzione Migration"
    
    print_info "Esecuzione migration..."
    php artisan migrate --force
    
    print_success "Migration completate"
    
    # Verifica migration status
    print_info "Verifica migration status..."
    php artisan migrate:status | grep slope
else
    print_warning "Migration saltate (--dry-run)"
fi

# 5. Verifica Struttura Database
print_header "5. Verifica Struttura Database"

if [ "$DRY_RUN" = false ]; then
    print_info "Verifica campo 'stato' in slope_bookings..."
    
    # Query per verificare tipo campo
    FIELD_TYPE=$(mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -se "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'slope_bookings' AND COLUMN_NAME = 'stato';" 2>/dev/null)
    
    if [ "$FIELD_TYPE" = "varchar" ]; then
        print_success "Campo 'stato' è varchar ✓"
    else
        print_error "Campo 'stato' non è varchar (attuale: $FIELD_TYPE)"
        exit 1
    fi
fi

# 6. Test Connessione API
print_header "6. Test Connessione API"

print_info "Test connessione API Slope..."
if php artisan slope:sync --test-connection; then
    print_success "Connessione API verificata"
else
    print_error "Test connessione fallito"
    print_warning "Verificare bearer token per ogni location"
    exit 1
fi

# 7. Dry Run Sincronizzazione
print_header "7. Test Sincronizzazione (Dry Run)"

print_info "Esecuzione dry run..."
php artisan slope:sync --dry-run

print_success "Dry run completato"

# 8. Prima Sincronizzazione Reale
if [ "$DRY_RUN" = false ]; then
    print_header "8. Prima Sincronizzazione Reale"
    
    print_warning "Procedere con la prima sincronizzazione reale? (y/n)"
    read -r response
    
    if [[ "$response" =~ ^[Yy]$ ]]; then
        print_info "Esecuzione sincronizzazione..."
        
        if php artisan slope:sync; then
            print_success "Sincronizzazione completata con successo"
        else
            print_error "Sincronizzazione fallita"
            print_info "Controlla i log: storage/logs/laravel.log"
            exit 1
        fi
    else
        print_warning "Sincronizzazione saltata"
    fi
else
    print_warning "Sincronizzazione reale saltata (--dry-run)"
fi

# 9. Verifica Risultati
if [ "$DRY_RUN" = false ]; then
    print_header "9. Verifica Risultati"
    
    print_info "Statistiche prenotazioni per stato:"
    mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SELECT stato, COUNT(*) as count FROM slope_bookings GROUP BY stato;" 2>/dev/null
    
    print_info "\nUltime 5 prenotazioni sincronizzate:"
    mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SELECT slope_booking_id, cliente, data, stato, synced_at FROM slope_bookings ORDER BY synced_at DESC LIMIT 5;" 2>/dev/null
fi

# 10. Riepilogo
print_header "DEPLOYMENT COMPLETATO"

print_success "Deployment completato con successo!"
echo ""
print_info "Prossimi passi:"
echo "  1. Verifica i log: tail -f storage/logs/laravel.log | grep Slope"
echo "  2. Configura cron job per esecuzione automatica"
echo "  3. Monitora le prime sincronizzazioni"
echo "  4. Consulta SLOPE_VERIFICATION_CHECKLIST.md per verifica completa"
echo ""

if [ "$DRY_RUN" = true ]; then
    print_warning "NOTA: Eseguito in modalità DRY-RUN. Nessuna modifica al database."
    print_info "Esegui senza --dry-run per deployment reale."
fi

print_info "Backup salvato in: $BACKUP_FILE"
print_info "Documentazione: SLOPE_README.md"

exit 0
