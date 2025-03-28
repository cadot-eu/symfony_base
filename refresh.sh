while true; do
    docker exec -it $(basename "$(pwd)") bin/console asset-map:compile
    inotifywait -e modify,create,delete -r assets
    sleep 1
done
