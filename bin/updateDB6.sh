. bin/db.inc

echo 'ALTER TABLE parlementaire ADD url_ancien_cpc TEXT NOT NULL , ADD url_nouveau_cpc TEXT NOT NULL'  | mysql $MYSQLID $DBNAME

php symfony doctrine:build-model
