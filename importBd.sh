#!/bin/bash

# 📌 Récupérer le nom du projet (nom du dossier courant)
PROJECT_NAME=$(basename "$PWD")

# 📌 Construire le nom du conteneur de la base de données
CONTAINER_NAME="${PROJECT_NAME}-db"

# 📌 Trouver le dernier fichier de sauvegarde disponible
LATEST_DB_BACKUP=$(ls -t backup_*.sql 2>/dev/null | head -n 1)
LATEST_UPLOADS_BACKUP=$(ls -t uploads_backup_*.zip 2>/dev/null | head -n 1)

# 📌 Vérifier si des fichiers de sauvegarde existent
if [[ -z "$LATEST_DB_BACKUP" && -z "$LATEST_UPLOADS_BACKUP" ]]; then
    echo "❌ Aucun fichier de sauvegarde trouvé. Abandon."
    exit 1
fi

echo "📂 Fichiers de sauvegarde détectés :"
[[ -n "$LATEST_DB_BACKUP" ]] && echo "   📌 Base de données : $LATEST_DB_BACKUP"
[[ -n "$LATEST_UPLOADS_BACKUP" ]] && echo "   📌 Uploads : $LATEST_UPLOADS_BACKUP"
echo ""

# 📌 Demande de confirmation
read -p "⚠️  Es-tu sûr de vouloir restaurer ces fichiers ? (oui/non) : " CONFIRMATION
if [[ "$CONFIRMATION" != "oui" ]]; then
    echo "❌ Import annulé."
    exit 0
fi

# 📌 Importer la base de données
if [[ -n "$LATEST_DB_BACKUP" ]]; then
    echo "📥 Importation de la base de données dans $CONTAINER_NAME..."
    cat "$LATEST_DB_BACKUP" | docker exec -i "$CONTAINER_NAME" psql -U app -d app

    if [[ $? -eq 0 ]]; then
        echo "✅ Base de données restaurée avec succès."
    else
        echo "❌ Erreur lors de l'importation de la base de données."
        exit 1
    fi
else
    echo "⚠️ Aucun fichier de sauvegarde de base de données trouvé."
fi

# 📌 Restaurer le dossier public/uploads
if [[ -n "$LATEST_UPLOADS_BACKUP" ]]; then
    echo "📦 Restauration du dossier public/uploads..."
    unzip -o "$LATEST_UPLOADS_BACKUP" -d ./

    if [[ $? -eq 0 ]]; then
        echo "✅ Uploads restaurés avec succès."
    else
        echo "❌ Erreur lors de la restauration des fichiers uploads."
        exit 1
    fi
else
    echo "⚠️ Aucun fichier de sauvegarde d'uploads trouvé."
fi

echo "🎉 Restauration terminée avec succès !"
