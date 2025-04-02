#!/bin/bash

# 📌 Demande interactive pour les paramètres
read -p "Nom du conteneur PostgreSQL (par défaut : database) : " CONTAINER_NAME
CONTAINER_NAME=${CONTAINER_NAME:-database}

read -p "Nom de la base de données : " DB_NAME
read -p "Nom d'utilisateur PostgreSQL : " PG_USER
read -sp "Mot de passe PostgreSQL : " PG_PASSWORD
echo ""

# 📌 Lister les fichiers de sauvegarde disponibles
SQL_FILES=(*.sql)
if [[ ${#SQL_FILES[@]} -eq 0 ]]; then
    echo "❌ Aucun fichier SQL trouvé pour l'importation."
    exit 1
fi

echo "📂 Fichiers de sauvegarde disponibles :"
for i in "${!SQL_FILES[@]}"; do
    echo "   [$i] ${SQL_FILES[$i]}"
done

# 📌 Demander à l'utilisateur de choisir un fichier
read -p "Quel fichier veux-tu importer ? (numéro) : " FILE_INDEX
if ! [[ "$FILE_INDEX" =~ ^[0-9]+$ ]] || (( FILE_INDEX < 0 || FILE_INDEX >= ${#SQL_FILES[@]} )); then
    echo "❌ Sélection invalide."
    exit 1
fi

SELECTED_FILE="${SQL_FILES[$FILE_INDEX]}"
echo "📥 Fichier sélectionné : $SELECTED_FILE"

# 📌 Vérifier si la base de données existe, sinon la créer
echo "🔍 Vérification de l'existence de la base '$DB_NAME'..."
export PGPASSWORD=$PG_PASSWORD
DB_EXISTS=$(docker exec -t "$CONTAINER_NAME" psql -U "$PG_USER" -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" | tr -d '[:space:]')

if [[ "$DB_EXISTS" != "1" ]]; then
    echo "⚠️  La base '$DB_NAME' n'existe pas. Création en cours..."
    docker exec -t "$CONTAINER_NAME" psql -U "$PG_USER" -d postgres -c "CREATE DATABASE \"$DB_NAME\";"
    echo "✅ Base '$DB_NAME' créée avec succès."
else
    echo "✅ La base '$DB_NAME' existe déjà."
fi

# 📌 Demande de confirmation avant l'import
read -p "⚠️  Es-tu sûr de vouloir restaurer '$SELECTED_FILE' dans '$DB_NAME' ? (oui/non) : " CONFIRM
if [[ "$CONFIRM" != "oui" ]]; then
    echo "❌ Import annulé."
    exit 1
fi

# 📌 Importer la base de données
echo "📥 Importation en cours..."
docker exec -i "$CONTAINER_NAME" psql -U "$PG_USER" -d "$DB_NAME" < "$SELECTED_FILE"

if [[ $? -eq 0 ]]; then
    echo "✅ Importation terminée avec succès."
else
    echo "❌ Erreur lors de l'importation."
    exit 1
fi

# 📌 Restaurer les fichiers uploadés si un fichier ZIP est trouvé
UPLOAD_ZIP="uploads_backup_${DB_NAME}_*.zip"
UPLOAD_ZIP_FILE=$(ls $UPLOAD_ZIP 2>/dev/null | head -n 1)

if [[ -n "$UPLOAD_ZIP_FILE" ]]; then
    echo "📦 Détection d'une sauvegarde des fichiers uploads : $UPLOAD_ZIP_FILE"
    read -p "⚠️  Veux-tu restaurer les fichiers uploads ? (oui/non) : " CONFIRM_UPLOADS
    if [[ "$CONFIRM_UPLOADS" == "oui" ]]; then
        unzip -o "$UPLOAD_ZIP_FILE" -d .
        echo "✅ Fichiers uploads restaurés."
    else
        echo "⚠️  Restauration des fichiers uploads annulée."
    fi
else
    echo "⚠️ Aucun fichier de sauvegarde d'uploads trouvé."
fi

echo "🎉 Restauration terminée avec succès !"
