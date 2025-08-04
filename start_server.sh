#!/bin/bash
cd /opt/sites/admin.middleworldfarms.org
nohup php artisan serve --host=0.0.0.0 --port=8444 > laravel_serve.log 2>&1 &
echo "Laravel server starting on port 8444..."
sleep 3
echo "Testing server..."
curl -s http://localhost:8444/ | head -5
