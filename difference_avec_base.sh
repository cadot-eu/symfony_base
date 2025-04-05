#!/bin/bash
# Vérifier si le répertoire ../base existe
if [ ! -d "../base" ]; then
    echo "Le répertoire ../base n'existe pas."
    exit 1
fi

# Lire les fichiers et dossiers à ignorer depuis .gitignore
# Ajouter .git, .env et Caddyfile par défaut
IGNORED_PATTERNS=(".git" ".env" "Caddyfile" "compose.yaml" ".env.dev" ".ignore")
for ignore_file in .gitignore .ignore; do
    if [ -f "$ignore_file" ]; then
        while IFS= read -r line; do
            # Ignorer les commentaires et lignes vides
            [[ "$line" =~ ^#.*$ || -z "$line" ]] && continue
            # Nettoyer le motif
            clean_pattern="${line#/}" # Supprimer le / initial
            IGNORED_PATTERNS+=("$clean_pattern")
        done < "$ignore_file"
    fi
done


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

# Créer des tableaux pour stocker les listes de fichiers
mapfile -t BASE_FILES < <(find "../base" -type f | sort)
mapfile -t CURRENT_FILES < <(find "." -type f | sort)

# Nettoyer les chemins pour faciliter la comparaison
BASE_CLEAN=()
for file in "${BASE_FILES[@]}"; do
    path="${file#../base/}"
    if ! should_ignore "$path"; then
        BASE_CLEAN+=("$path")
    fi
done

CURRENT_CLEAN=()
for file in "${CURRENT_FILES[@]}"; do
    path="${file#./}"
    if ! should_ignore "$path"; then
        CURRENT_CLEAN+=("$path")
    fi
done

# Tableaux pour stocker les différences
DELETED_FILES=()
ADDED_FILES=()
MODIFIED_FILES=()

# Trouver les fichiers supprimés (dans base mais pas dans le répertoire courant)
for file in "${BASE_CLEAN[@]}"; do
    if ! printf '%s\n' "${CURRENT_CLEAN[@]}" | grep -q "^$file$"; then
        echo "Supprimer: $file"
        DELETED_FILES+=("$file")
    fi
done


IGNORE_FILE=".ignore"
FILTERED_FILES=()

# Read ignored files from .ignore
if [ -f "$IGNORE_FILE" ]; then
    mapfile -t IGNORE_LIST < "$IGNORE_FILE"
fi

# Trouver les fichiers ajoutés (dans le répertoire courant mais pas dans base)
for file in "${CURRENT_CLEAN[@]}"; do
    if ! printf '%s\n' "${BASE_CLEAN[@]}" | grep -q "^$file$"; then
        if printf '%s\n' "${IGNORE_LIST[@]}" | grep -q "^$file$"; then
            continue
        fi
        echo "Ajouter: $file"
        ADDED_FILES+=("$file")
    fi
done




# Trouver les fichiers modifiés (dans les deux mais différents)
for file in "${BASE_CLEAN[@]}"; do
    if printf '%s\n' "${CURRENT_CLEAN[@]}" | grep -q "^$file$"; then
        if ! cmp -s "../base/$file" "./$file"; then
            echo "Modifier: $file"
            MODIFIED_FILES+=("$file")
        fi
    fi
done

# Proposer la copie vers ../base si des différences ont été trouvées
if [ ${#ADDED_FILES[@]} -gt 0 ] || [ ${#MODIFIED_FILES[@]} -gt 0 ] || [ ${#DELETED_FILES[@]} -gt 0 ]; then
    echo ""
    echo "Des différences ont été trouvées entre le répertoire actuel et ../base."
    read -p "Voulez-vous effectuer la synchronisation des fichiers du répertoire actuel vers ../base et supprimer les fichiers supprimés sur ../base? (O/n) " response
    response=${response,,}  # Mettre en minuscule
    
    if [[ -z "$response" || "$response" == "o" || "$response" == "y" ]]; then
        # Copier les fichiers ajoutés et modifiés
        for file in "${ADDED_FILES[@]}" "${MODIFIED_FILES[@]}"; do
            target_dir=$(dirname "../base/$file")
            mkdir -p "$target_dir"
            cp "./$file" "../base/$file"
            echo "Copié: $file vers ../base/$file"
        done
        
        # Supprimer les fichiers qui ont été supprimés localement
        for file in "${DELETED_FILES[@]}"; do
            if [ -f "../base/$file" ]; then
                rm "../base/$file"
                echo "Supprimé: ../base/$file"
            fi
        done
        
        echo "Synchronisation terminée."
    else
        echo "Aucune modification n'a été effectuée."
    fi
else
    echo "Aucune différence trouvée entre le répertoire actuel et ../base."
fi