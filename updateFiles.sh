#!/bin/bash

# Récupère le nom du répertoire courant
PROJECT_NAME=$(basename "$PWD")
NUMPORT=$(shuf -i 8000-8999 -n 1)
# Fichiers à modifier
FILES=("Caddyfile" "compose.yaml" "compose.override.yaml" ".env" ".env.dev")

# Vérifier si les fichiers existent avant modification
for FILE in "${FILES[@]}"; do
    if [[ -f "$FILE" ]]; then
        echo "Mise à jour de $FILE..."
        sed -i "s/DIRECTORY/$PROJECT_NAME/g" "$FILE"
        sed -i "s/NUMPORT/$NUMPORT/g" "$FILE"
        
    else
        echo "⚠️ Le fichier $FILE n'existe pas, il sera ignoré."
    fi
done

echo "✅ Remplacement terminé : 'DIRECTORY' → '$PROJECT_NAME' dans ${FILES[*]}"
