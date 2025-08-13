[0;1;32m●[0m ollama.service - Ollama Service
     Loaded: loaded (]8;;file://admiring-mccarthy.212-227-14-88.plesk.page/etc/systemd/system/ollama.service/etc/systemd/system/ollama.service]8;;; [0;1;32menabled[0m; preset: [0;1;32menabled[0m)
     Active: [0;1;32mactive (running)[0m since Wed 2025-08-13 16:19:40 UTC; 17min ago
   Main PID: 340522 (ollama)
      Tasks: 24 (limit: 19091)
     Memory: 6.5G (peak: 7.0G)
        CPU: 10min 50.687s
     CGroup: /system.slice/ollama.service
             ├─[0;38;5;245m340522 /usr/local/bin/ollama serve[0m
             └─[0;38;5;245m340713 /usr/local/bin/ollama runner --model /usr/share/ollama/.ollama/models/blobs/sha256-f5074b1221da0f5a2910d33b642efa5b9eb58cfdddca1c79e16d7ad28aa2b31f --ctx-size 4096 --batch-size 512 --threads 4 --no-mmap --parallel 1 --port 37769[0m

Aug 13 16:27:30 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:27:30 | 200 |      31.569µs |       127.0.0.1 | HEAD     "/"
Aug 13 16:27:30 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:27:30 | 200 |   11.983049ms |       127.0.0.1 | POST     "/api/show"
Aug 13 16:27:34 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:27:34 | 200 |  3.560318325s |       127.0.0.1 | POST     "/api/generate"
Aug 13 16:28:08 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:28:08 | 500 | 10.009342917s |       127.0.0.1 | POST     "/api/generate"
Aug 13 16:30:32 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:30:32 | 200 |      35.987µs |       127.0.0.1 | HEAD     "/"
Aug 13 16:30:32 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:30:32 | 200 |   11.884794ms |       127.0.0.1 | POST     "/api/show"
Aug 13 16:30:36 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:30:36 | 200 |  3.369076859s |       127.0.0.1 | POST     "/api/generate"
Aug 13 16:31:11 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:31:11 | 500 | 25.024466301s |       127.0.0.1 | POST     "/api/generate"
Aug 13 16:32:49 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:32:49 | 500 | 25.024885742s |       127.0.0.1 | POST     "/api/generate"
Aug 13 16:35:34 admiring-mccarthy.212-227-14-88.plesk.page ollama[340522]: [GIN] 2025/08/13 - 16:35:34 | 500 | 25.024964833s |       127.0.0.1 | POST     "/api/generate"
