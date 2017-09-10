<?php

/**
 * Class Component_FullCache
 */
class Component_FullCache extends CMS_Component implements Core_ModuleInterface
{
    /**
     * Папка сохранения файлов кеша страниц
     *
     * @var string $cache_dir
     */
    protected $cache_dir = '../cache/FullCache/';

    /**
     * Массив догружаемых ajax_insertions (вставок)
     *
     * @var array $ajax_insertions
     */
    protected $ajax_insertions = [];

    /**
     * Генерирует md5 (имя файла кеша) по url страницы
     * по умолчанию берет url текущей стрницы без GET параметров
     *
     * @param string $uri
     *
     * @return string
     */
    public function get_uri_hash($uri = '')
    {
        return md5($uri ?: WS::env()->request->path);
    }

    /**
     * Если есть блоки с отложенной ajax загрузкой,
     * то добавляет в HEAD служебный js скрипт для ajax запроса нужного контента
     *
     * для корректной работы скрипта необходим jQuery
     *
     * @param $view CMS_Views_View
     */
    public function build_head($view)
    {
        if ($this->ajax_insertions) {
            $view->use_script(CMS::component_static_path('scripts/ajax_load.js', 'FullCache'));
        }
    }

    /**
     * Переопределенный метод обработки insertions
     *
     * @param $name string
     * @param $str string
     * @param $res string
     * @param $parms array
     * @param $original_str string
     * @param $args array
     */
    public function cms_insertions(&$name, $str, &$res, $parms, $original_str, $args)
    {
        $modifier = $parms['modifer'];

        if ($modifier && mb_strtolower($modifier) == 'ajax_load') {
            $str_without_modifier = $this->remove_ajax_load_modifier($original_str);
            $res = $this->ajax_insertions[$str_without_modifier] = trim(CMS::process_insertions($str_without_modifier));
        }
    }

    /**
     * Удаляет из insertion-строки
     * ajax модификатор отложенной загрузки
     *
     * @param string $original_str
     *
     * @return string
     */
    public function remove_ajax_load_modifier($original_str)
    {
        return trim(str_replace(':AJAX_LOAD', '', $original_str));
    }

    /**
     * HTML обертка блока отложенной ajax загрузки
     * важен класс ajax-load-insertions
     * и атрибут data-insertion-str, содержащий insertion строку
     *
     * @param string $insertion_str
     *
     * @return string
     */
    public function block_for_load_insertion($insertion_str)
    {
        return '<div class="ajax-load-insertions" data-insertion-str="' . $insertion_str . '"></div>';
    }

    /**
     * Добавляет блоки с отложенной загрузкой
     * (ajax insertions)
     *
     * @param string $content
     *
     * @return string
     */
    public function add_blocks($content)
    {
        foreach ($this->ajax_insertions as $insertion_str => $html) {
            $content = str_replace($html, $this->block_for_load_insertion($insertion_str), $content);
        }
        return $content;
    }

    /**
     * Удаляет из контента страницы лишние переводы
     * строк и пробелы
     *
     * @param string $response_body
     *
     * @return string
     */
    public function prepare_response_body($response_body)
    {
        if ($this->ajax_insertions) {
            $response_body = $this->add_blocks($response_body);
        }
        return str_replace(["\r", "\t", '   '], '', trim($response_body));
    }

    /**
     * Сохраняет подготоленный response-объект страницы
     * в файл
     *
     * @param $response Net_HTTP_Response
     */
    public function save($response)
    {
        IO_FS::mkdir($this->cache_dir, 0755);
        IO_FS::FileStream($this->cache_dir . $this->get_uri_hash(), 'w+')
            ->write(
                json_encode([
                    'headers' => $response->headers->as_array(true),
                    'body' => $this->prepare_response_body($response->body),
                    'status_code' => (int)$response->status->code,
                    'status' => $response->status->as_string(),
                ], JSON_UNESCAPED_UNICODE))
            ->close();
    }

    /**
     * Лезет в конфиг settings текущего компонента
     * и ищет там запрет кеширования disallow_cache
     *
     * @return bool
     */
    public function component_allow_cache()
    {
        $component = CMS::component();

        if (!$component || !$component->config('settings')) {
            return true;
        }

        $config_param = $component->config('settings')->disallow_cache;
        return !(isset($config_param) && !$config_param);
    }

    /**
     * Комплексное условие запуска кеширования отпределенной страницы
     * Условия:
     * - Статус страницы 200
     * - Компонент (если есть), отвечающий за эту страницу разрешает кеширование, подробности см. в методе component_allow_cache
     * - Не указана глобальная настройка (disallow_cache), запрещающая кэширование
     * - Текущий запрос не ajax (сомнительное ограничение)
     * - Это не CLI запрос
     * - Кеширование админиских страниц не разрешено
     *
     * TODO: подумать о грамотном кешировании ajax и CLI запросов
     *
     * @param $response Net_HTTP_Response
     *
     * @return bool
     */
    public function save_response_condition($response)
    {
        return (int)$response->status->code == 200
            && $this->component_allow_cache()
            && !WS::env()->no_cache
            && !WS::env()->request->is_xhr()
            && !CMS::$is_cli
            && !CMS::$in_admin;
    }

    /**
     * Вызов кеширования response-объекта (при соблюдении условий из метода save_response_condition)
     *
     * @param $response Net_HTTP_Response
     */
    public function response($response)
    {
        if ($this->save_response_condition($response)) {
            $this->save($response);
        }
    }

    /**
     * Очищает кэш
     *
     * Если указан url, то кеш будет очищен только для
     * указанной страницы
     *
     * @param string $url
     */
    public function wipe($url = '')
    {
        $url ? IO_FS::rm($this->cache_dir . $this->get_uri_hash($url)) : CMS::rmdir($this->cache_dir);
    }

    //STATIC METHODS (CALLED BY EVENT) (config/component.php)
    public static function event_response($response)
    {
        self::instance()->response($response);
    }

    public static function event_cms_insertions(&$name, $str, &$res, $parms, $original_str, $args)
    {
        self::instance()->cms_insertions($name, $str, $res, $parms, $original_str, $args);
    }

    public static function event_build_head($view)
    {
        self::instance()->build_head($view);
    }

}

/**
 * Class Component_FullCache_Router
 */
class Component_FullCache_Router extends CMS_Router
{
    protected $controllers = array(
        'Component.FullCache.Controller' => array(
            'path' => '/ajax-load-insertions',
        ),
    );
}