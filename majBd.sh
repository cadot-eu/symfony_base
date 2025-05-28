#!/bin/bash

# Charge la variable SSH depuis .env.local
SSH_HOST=$(grep '^SSH=' .env.local | cut -d '=' -f2)

if [ -z "$SSH_HOST" ]; then
    echo "Erreur : la variable SSH est introuvable dans .env.local"
    exit 1
fi

# Récupère le nom du jour et du répertoire courant
JOUR=$(date +"%A")
NOM_REP=$(basename "$PWD")

# Supprime les conteneurs et le volume
dkillall
docker volume rm ${NOM_REP}_database_data-${NOM_REP}
dc up -d

# Copie les fichiers depuis l'hôte distant défini dans SSH
scp ${SSH_HOST}:backups/backup_${NOM_REP}_${JOUR}.sql .
scp ${SSH_HOST}:backups/uploads_backup_${NOM_REP}_${JOUR}.tar.gz .

# Importation de la base
importBd.sh ${NOM_REP}-db db${NOM_REP} ${NOM_REP} pass backup_${NOM_REP}_${JOUR}.sql

# Mise à jour du schéma Doctrine
docker exec ${NOM_REP} php bin/console doctrine:schema:update --force

# Gestion des fichiers uploads
if [ -d "public/uploads" ]; then
    sudo rm -rf public/uploads
fi

if [ -f "uploads_backup_${NOM_REP}_${JOUR}.tar.gz" ]; then
    sudo tar -xzf uploads_backup_${NOM_REP}_${JOUR}.tar.gz -C .
    sudo chown -R www-data:www-data public/uploads
    sudo chmod -R 755 public/uploads
    rm uploads_backup_${NOM_REP}_${JOUR}.tar.gz
fi

rm backup_${NOM_REP}_${JOUR}.sql

echo "✅ Mise à jour terminée"