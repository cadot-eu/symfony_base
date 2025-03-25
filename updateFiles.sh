#!/bin/bash

# Récupère le nom du répertoire courant
PROJECT_NAME=$(basename "$PWD")

# Fichiers à modifier
FILES=("Caddyfile" "compose.yaml" "compose.override.yaml")

# Vérifier si les fichiers existent avant modification
for FILE in "${FILES[@]}"; do
    if [[ -f "$FILE" ]]; then
        echo "Mise à jour de $FILE..."
        sed -i "s/\bbase\b/$PROJECT_NAME/g" "$FILE"
    else
        echo "⚠️ Le fichier $FILE n'existe pas, il sera ignoré."
    fi
done

echo "✅ Remplacement terminé : 'base' → '$PROJECT_NAME' dans ${FILES[*]}"
