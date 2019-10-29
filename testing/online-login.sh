#!/bin/bash

curl -c session.cookie -X POST -H "Content-Type: application/x-www-form-urlencoded" -d "username=pascal2019&password=halfl1fe" http://app.jugendbuero.be/aplan2/index.php

curl -b session.cookie http://app.jugendbuero.be/aplan2/index.php
curl -b session.cookie http://app.jugendbuero.be/aplan2/userinfogui.php
