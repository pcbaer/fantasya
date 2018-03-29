#!/bin/bash

HOST=localhost
DATABASE=
USER=
PASSWORD=''
LAST_TURN=`mysql -N -h $HOST -D $DATABASE -u $USER -p$PASSWORD -e "SELECT Value FROM settings WHERE Name='game.runde'"`
TURN=`expr $LAST_TURN + 1`
GAME=spiel
BASE_DIR=/home/fantasya/games/$GAME
REPORT_DIR=reporte
ZIP_DIR=zip
LOG_DIR=log

cd $BASE_DIR
echo "Running turn $TURN..."
echo

echo "Running the game..."
java -jar fantasya.jar -server $HOST:3306 -datenbank $DATABASE -benutzer $USER -passwort $PASSWORD -zat
ZAT_RESULT=$?
echo "Exit code: $ZAT_RESULT"
mv log-*.txt $LOG_DIR
if [ $ZAT_RESULT -gt 0 ]
then
	echo "Game aborted!"
	exit 1
fi
echo

echo "Moving reports..."
mkdir $REPORT_DIR/$TURN
mv $REPORT_DIR/$TURN-* $REPORT_DIR/$TURN/
for zip in $ZIP_DIR/$TURN/*.zip
do
	mv $zip $ZIP_DIR/$TURN/$TURN-$(basename $zip)
done