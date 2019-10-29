#!/bin/bash
HOST=http://localhost:8080

curl -c session.cookie -X POST -H "Content-Type: application/x-www-form-urlencoded" -d "username=pascal2019&password=halfl1fe" $HOST/index.php

curl -b session.cookie $HOST/userinfogui.php
