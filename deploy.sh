#!/bin/bash

# Beweb Tirocinio - Deploy Script per SiteGround
# ================================================
# Uso: ./deploy.sh [staging|production]

# Configurazione
ENVIRONMENT=${1:-production}
LOCAL_DIR="C:/laragon/www/tirocinio/beweb-app"
REMOTE_HOST="tirocinio.clementeteodonno.it"
REMOTE_USER="root"
REMOTE_DIR="/home/customer/www/tirocinio.clementeteodonno.it/public_html"

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}==================================${NC}"
echo -e "${GREEN}Beweb Tirocinio - Deployment${NC}"
echo -e "${GREEN}==================================${NC}"
echo -e "Environment: ${YELLOW}$ENVIRONMENT${NC}"
echo -e "Target: ${YELLOW}$REMOTE_HOST${NC}"
echo ""

# Step 1: Verifica connessione SSH
echo -e "${YELLOW}[1/7] Verificando connessione SSH...${NC}"
ssh -o ConnectTimeout=5 $REMOTE_USER@$REMOTE_HOST "echo 'SSH OK'" > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "${RED}Errore: Impossibile connettersi via SSH${NC}"
    echo "Assicurati che le chiavi SSH siano configurate correttamente"
    exit 1
fi
echo -e "${GREEN}✓ Connessione SSH OK${NC}"

# Step 2: Backup remoto
echo -e "${YELLOW}[2/7] Creando backup remoto...${NC}"
BACKUP_NAME="backup_$(date +%Y%m%d_%H%M%S).tar.gz"
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && tar -czf ../backups/$BACKUP_NAME . 2>/dev/null || mkdir -p ../backups && tar -czf ../backups/$BACKUP_NAME ."
echo -e "${GREEN}✓ Backup creato: $BACKUP_NAME${NC}"

# Step 3: Prepara files locali
echo -e "${YELLOW}[3/7] Preparando files per deployment...${NC}"

# Copia .env.production come .env se in produzione
if [ "$ENVIRONMENT" = "production" ]; then
    cp .env.production .env.tmp
fi

# Crea lista esclusioni
cat > .rsync-exclude <<EOF
.git/
.gitignore
node_modules/
.env
.env.local
.env.production
*.log
test_*.php
deploy.sh
.rsync-exclude
istruzioni_sonnet/
temp/
cache/
*.md
.vscode/
.idea/
EOF

echo -e "${GREEN}✓ Files preparati${NC}"

# Step 4: Sincronizza files
echo -e "${YELLOW}[4/7] Sincronizzando files...${NC}"
rsync -avz --delete \
    --exclude-from='.rsync-exclude' \
    --progress \
    ./ \
    $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/

if [ $? -ne 0 ]; then
    echo -e "${RED}Errore durante rsync${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Files sincronizzati${NC}"

# Step 5: Upload .env di produzione
if [ "$ENVIRONMENT" = "production" ] && [ -f ".env.tmp" ]; then
    echo -e "${YELLOW}[5/7] Caricando configurazione produzione...${NC}"
    scp .env.tmp $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/.env
    rm .env.tmp
    echo -e "${GREEN}✓ Configurazione caricata${NC}"
else
    echo -e "${YELLOW}[5/7] Mantenendo .env esistente...${NC}"
fi

# Step 6: Esegui comandi post-deployment
echo -e "${YELLOW}[6/7] Eseguendo comandi post-deployment...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

# Permessi directories
chmod 755 app config public_html
chmod -R 755 app/Views
chmod -R 777 temp cache 2>/dev/null || true

# Crea directories se non esistono
mkdir -p temp/audio cache 2>/dev/null
chmod 777 temp/audio cache 2>/dev/null

# Composer install (solo se composer.json è cambiato)
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader 2>/dev/null || true
fi

# Pulizia cache
rm -rf cache/* 2>/dev/null || true

# Database migrations (se necessario)
# php migrate.php

echo "Post-deployment completato"
ENDSSH

echo -e "${GREEN}✓ Comandi post-deployment eseguiti${NC}"

# Step 7: Verifica deployment
echo -e "${YELLOW}[7/7] Verificando deployment...${NC}"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://$REMOTE_HOST)

if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
    echo -e "${GREEN}✓ Sito risponde correttamente (HTTP $HTTP_STATUS)${NC}"
else
    echo -e "${YELLOW}⚠ Sito risponde con HTTP $HTTP_STATUS${NC}"
fi

# Cleanup
rm -f .rsync-exclude

echo ""
echo -e "${GREEN}==================================${NC}"
echo -e "${GREEN}✓ DEPLOYMENT COMPLETATO!${NC}"
echo -e "${GREEN}==================================${NC}"
echo ""
echo -e "URL: ${YELLOW}https://$REMOTE_HOST${NC}"
echo -e "Backup salvato in: ${YELLOW}$BACKUP_NAME${NC}"
echo ""
echo -e "${YELLOW}IMPORTANTE:${NC}"
echo "1. Verifica il sito: https://$REMOTE_HOST"
echo "2. Configura le API keys da: https://$REMOTE_HOST/ai/settings"
echo "3. Aggiorna database credentials in .env se necessario"
echo "4. Testa Smart Focus e login"