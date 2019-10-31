#!/bin/bash
HOST=http://localhost:8080

curl -s -c session.cookie -X POST -H "Content-Type: application/x-www-form-urlencoded" -d "username=pascal2019&password=halfl1fe" $HOST/index.php > /dev/null

curl -b session.cookie -X PUT -H "Content-Type: application/json" -d "@1006-rest-scheduleitem-update.txt" $HOST/rest/schedule_items/update.php

