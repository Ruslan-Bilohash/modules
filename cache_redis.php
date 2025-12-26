<?php

// Инициализация подключения к Redis
function get_redis_connection() {
    static $redis = null;
    if ($redis === null) {
        $settings = get_cache_settings(); // Предполагается, что функция доступна из functions_cache.php
        if ($settings['redis_enabled']) {
            try {
                $redis = new Redis();
                $redis->connect($settings['redis_host'], $settings['redis_port']);
                if (!empty($settings['redis_password'])) {
                    $redis->auth($settings['redis_password']);
                }
            } catch (Exception $e) {
                error_log("Ошибка подключения к Redis: " . $e->getMessage());
                $redis = false; // Отключаем Redis при ошибке
            }
        } else {
            $redis = false; // Redis отключен в настройках
        }
    }
    return $redis;
}

// Получение данных из кеша Redis
function get_from_redis_cache($cache_key) {
    $redis = get_redis_connection();
    if ($redis !== false) {
        $cache_data = $redis->get($cache_key);
        if ($cache_data !== false) {
            return unserialize($cache_data); // Данные найдены в Redis
        }
    }
    return false;
}

// Сохранение данных в кеш Redis
function save_to_redis_cache($cache_key, $data, $path) {
    $settings = get_cache_settings();
    $redis = get_redis_connection();
    if ($redis !== false) {
        $cache_data = [
            'timestamp' => time(),
            'data' => $data
        ];
        $serialized = serialize($cache_data);
        $lifetime = $settings['cache_rules'][$path]['lifetime'] ?? $settings['default_lifetime'];
        $redis->setEx($cache_key, $lifetime, $serialized); // Устанавливаем с TTL
    }
}

// Очистка всего кеша в Redis
function clear_redis_cache() {
    $redis = get_redis_connection();
    if ($redis !== false) {
        $redis->flushAll(); // Очищаем все ключи в Redis
        return true;
    }
    return false;
}

// Очистка кеша для конкретного пути в Redis
function clear_redis_path_cache($path) {
    $redis = get_redis_connection();
    if ($redis !== false) {
        $keys = $redis->keys("*"); // Получаем все ключи
        foreach ($keys as $key) {
            if (strpos($key, 'db_') === 0 && $path === '/db' ||
                strpos($key, 'static_') === 0 && $path === '/cache/static' ||
                strpos($key, 'external_') === 0 && $path === '/cache/external') {
                $redis->del($key); // Удаляем ключи, соответствующие пути
            }
        }
        return true;
    }
    return false;
}

// Получение статистики Redis (опционально)
function get_redis_stats() {
    $redis = get_redis_connection();
    if ($redis !== false) {
        $info = $redis->info('MEMORY');
        return [
            'used_memory' => $info['used_memory'] ?? 0, // Используемая память в байтах
            'keys' => $redis->dbSize() // Количество ключей в текущей базе
        ];
    }
    return [
        'used_memory' => 0,
        'keys' => 0
    ];
}

?>