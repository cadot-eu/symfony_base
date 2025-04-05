JOUR=$(date +"%A" )
NOM_REP=$(basename "$PWD")

scp backup_user@cadot.eu:backups/backup_${NOM_REP}_${JOUR}.sql .
scp backup_user@cadot.eu:backups/uploads_backup_${NOM_REP}_${JOUR}.tar.gz .
importBd.sh  ${NOM_REP}-db  db${NOM_REP} ${NOM_REP}   pass  backup_${NOM_REP}_${JOUR}.sql

docker exec ${NOM_REP} php bin/console doctrine:schema:update --force
sudo rm -rf public/uploads
sudo tar -xzf uploads_backup_${NOM_REP}_${JOUR}.tar.gz  -C .
sudo chown -R www-data:www-data public/uploads
sudo chmod -R 755 public/uploads
rm backup_${NOM_REP}_${JOUR}.sql
rm uploads_backup_${NOM_REP}_${JOUR}.tar.gz