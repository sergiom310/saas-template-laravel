#!/bin/bash

# Configura estas variables según tu entorno
LOCAL_PATH="./storage/app/public/"
REMOTE_USER="root"
REMOTE_HOST="147.93.7.195"
REMOTE_PATH="/var/www/saas/backend/storage/app/public/"

# Subir archivos usando rsync
rsync -avz --delete "$LOCAL_PATH" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"