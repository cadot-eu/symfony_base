#!/bin/bash
# Vérifier si le répertoire ../base existe
if [ ! -d "../base" ]; then
    echo "Le répertoire ../base n'existe pas."
    exit 1
fi

# Lire les fichiers et dossiers à ignorer depuis .gitignore
# Ajouter .git, .env et Caddyfile par défaut
IGNORED_PATTERNS=(".git" ".env" "Caddyfile" "compose.yaml" ".env.dev")
if [ -f .gitignore ]; then
    while IFS= read -r line; do
        # Ignorer les commentaires et lignes vides
        [[ "$line" =~ ^#.*$ || -z "$line" ]] && continue
        # Nettoyer le motif
        clean_pattern="${line#/}"  # Supprimer le / initial
        IGNORED_PATTERNS+=("$clean_pattern")
    done < .gitignore
fi

# Fonction pour vérifier si un fichier ou un dossier doit être ignoré
should_ignore() {
    local file="$1"
    
    for pattern in "${IGNORED_PATTERNS[@]}"; do
        # Gérer les motifs avec jokers (*)
        if [[ "$pattern" == *"*"* ]]; then
            # Convertir le motif .gitignore en motif glob
            glob_pattern="${pattern//\*/*}"
            if [[ "$file" == $glob_pattern || "$file" == *"/$glob_pattern" ]]; then
                return 0
            fi
            # Gérer les motifs de dossiers (qui se terminent par /)
            elif [[ "$pattern" == *"/" ]]; then
            dir_pattern="${pattern%/}"
            if [[ "$file" == "$dir_pattern"/* || "$file" == "$dir_pattern" ]]; then
                return 0
            fi
            # Motifs normaux
            elif [[ "$file" == "$pattern" || "$file" == *"/$pattern" || "$file" == .git/* ]]; then
            return 0
        fi
    done
    
    return 1
}

# Stocker tous les fichiers dans un tableau plutôt qu'utiliser un pipe
mapfile -t BASE_FILES < <(find "../base" -type f)

# Traiter chaque fichier
for base_file in "${BASE_FILES[@]}"; do
    rel_path="${base_file#../base/}"
    target_file="./$rel_path"
    
    # Vérifier si le fichier ou son dossier parent est ignoré
    if should_ignore "$rel_path"; then
        continue
    fi
    
    if [ ! -e "$target_file" ] || ! cmp -s "$base_file" "$target_file"; then
        echo "Le fichier $rel_path est différent ou manquant."
        read -p "Copier depuis base ? (O/n) " response
        response=${response,,}  # Mettre en minuscule
        
        if [[ -z "$response" || "$response" == "o" || "$response" == "y" ]]; then
            mkdir -p "$(dirname "$target_file")"
            cp "$base_file" "$target_file"
            echo "$rel_path copié."
        fi
    fi
done

echo "Mise à jour des fichiers terminée. Pensez à lancer refresh.sh."