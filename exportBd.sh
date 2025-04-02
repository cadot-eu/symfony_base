#!/bin/bash

# 📌 Récupérer le nom du projet (nom du dossier courant)
PROJECT_NAME=$(basename "$PWD")

# 📌 Construire le nom du conteneur de la base de données
CONTAINER_NAME="${PROJECT_NAME}-db"

# 📌 Définir les noms des fichiers de sauvegarde
TIMESTAMP=$(date +'%Y-%m-%d_%H-%M-%S')
DB_BACKUP_FILE="backup_${TIMESTAMP}.sql"
UPLOADS_BACKUP_FILE="uploads_backup_${TIMESTAMP}.zip"

echo "🔄 Début de la sauvegarde..."

# 📌 Sauvegarde de la base de données
echo "📤 Exportation de la base de données depuis le conteneur $CONTAINER_NAME..."
docker exec -t "$CONTAINER_NAME" pg_dump -U app -d app > "$DB_BACKUP_FILE"

if [[ $? -eq 0 ]]; then
    echo "✅ Base de données sauvegardée : $DB_BACKUP_FILE"
else
    echo "❌ Erreur lors de l'export de la base de données"
    exit 1
fi

# 📌 Sauvegarde du dossier public/uploads
if [[ -d "public/uploads" ]]; then
    echo "📦 Compression du dossier public/uploads..."
    zip -r "$UPLOADS_BACKUP_FILE" public/uploads
    echo "✅ Dossier uploads sauvegardé : $UPLOADS_BACKUP_FILE"
else
    echo "⚠️ Le dossier public/uploads n'existe pas, aucune sauvegarde effectuée."
fi

echo "🎉 Sauvegarde terminée avec succès !"
