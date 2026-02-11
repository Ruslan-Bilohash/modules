# Модуль Інтеграції Redis (Redis Integration)
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Функції для підключення/отримання/збереження/очищення/статистики Redis кешу (з TTL, префіксами, SCAN для ефективності, додаткові метрики).  
## Українська версія  
### Загальний огляд  
Модуль для роботи з Redis: статичне підключення з авторизацією/таймаутом/вибором DB, отримання/збереження даних (serialize з timestamp/TTL), очищення (flushDB/SCAN по префіксам: db_/static_/external_), статистика (used/peak memory, keys, hits/misses). Додатково: exists/delete ключів. Безпека: try-catch, error_log, class_exists перевірка.  
### Список файлів та їх призначення  
- **cache_redis.php** — Функції: підключення, get/set (з TTL), clear (all/path з SCAN/log), stats (info MEMORY/keyspace), exists/del ключів.  
### Основний функціонал  
- Підключення: Static з settings (host/port/password/db), таймаут 5с, auth/select DB.  
- Get/Set: Unserialize/serialize з timestamp, setEx/set за lifetime (>0/0).  
- Clear: FlushDB для all, SCAN/delete по префіксам (db_/static_/external_), логування deleted.  
- Stats: Human-readable used/peak memory, keys, hits/misses.  
- Додатково: Exists/del для ключів.  
- Безпека: Exceptions/error_log, false при помилках/відключенні.  
---  
## English Version  
### General Overview  
Module for Redis operations: static connection with auth/timeout/DB select, get/set data (serialize with timestamp/TTL), clear (flushDB/SCAN by prefixes: db_/static_/external_), stats (used/peak memory, keys, hits/misses). Additional: exists/delete keys. Security: try-catch, error_log, class_exists check.  
### List of Files and Their Purpose  
- **cache_redis.php** — Functions: connection, get/set (with TTL), clear (all/path with SCAN/log), stats (info MEMORY/keyspace), exists/del keys.  
### Main Functionality  
- Connection: Static from settings (host/port/password/db), 5s timeout, auth/select DB.  
- Get/Set: Unserialize/serialize with timestamp, setEx/set based on lifetime (>0/0).  
- Clear: FlushDB for all, SCAN/delete by prefixes (db_/static_/external_), log deleted.  
- Stats: Human-readable used/peak memory, keys, hits/misses.  
- Additional: Exists/del for keys.  
- Security: Exceptions/error_log, false on errors/disabled.  
---  
## Norsk Versjon  
### Generell Oversikt  
Modul for Redis-operasjoner: statisk tilkobling med auth/timeout/DB-valg, hent/lagre data (serialize med timestamp/TTL), tøm (flushDB/SCAN etter prefikser: db_/static_/external_), statistikk (brukt/topp minne, nøkler, hits/misses). Tillegg: exists/slett nøkler. Sikkerhet: try-catch, error_log, class_exists sjekk.  
### Liste over Filer og Deres Formål  
- **cache_redis.php** — Funksjoner: tilkobling, hent/lagre (med TTL), tøm (alle/bane med SCAN/log), statistikk (info MEMORY/keyspace), exists/slett nøkler.  
### Hovedfunksjonalitet  
- Tilkobling: Statisk fra innstillinger (host/port/password/db), 5s timeout, auth/valg DB.  
- Hent/Lagre: Unserialize/serialize med timestamp, setEx/set basert på lifetime (>0/0).  
- Tøm: FlushDB for alle, SCAN/slett etter prefikser (db_/static_/external_), logg slettet.  
- Statistikk: Lesbar brukt/topp minne, nøkler, hits/misses.  
- Tillegg: Exists/slett for nøkler.  
- Sikkerhet: Unntak/error_log, false ved feil/deaktivert.
