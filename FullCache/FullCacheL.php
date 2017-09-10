<?php

/**
 * Данный класс предназначен для
 * поиска сохраненного кеша страницы и его показа
 *
 * Во время работы методов данного класса ни CMS, ни БД еще не инициализированы,
 * поэтому использование их методов приведет к ошибке. После показа страницы из кеша работа сайта прерывается.
 *
 * Сохранением response-объекта в кеш занимается класс FullCache
 *
 * Class FullCacheL
 */
class FullCacheL
{
    /**
     * Экземпляр текщего класса
     *
     * @return FullCacheL
     */
    public static function instance()
    {
        return new self();
    }

    /**
     * md5 от path текущего url
     *
     * @return string
     */
    public function get_uri_hash()
    {
        return md5(parse_url($_SERVER['REQUEST_URI'])['path']);
    }

    /**
     * Проверка текущего запроса
     * если ajax - вернёт true
     *
     * @return bool
     */
    public function is_ajax()
    {
        $h = $_SERVER['HTTP_X_REQUESTED_WITH'];
        return ($h && mb_strtolower($h) == 'xmlhttprequest');
    }

    /**
     * Проверка существования файла с кешом
     * Если файл есть, вернёт массив с контентом, header'ами и проч.
     * если файла нет, вернут пустой массив
     *
     * @return array
     */
    public function has_cache()
    {
        $f_name = '../cache/FullCache/' . $this->get_uri_hash();
        return is_file($f_name) ? json_decode(file_get_contents($f_name), true) : [];
    }

    /**
     * Показ контента из файла (если таковой имеется)
     *
     * @return null
     */
    public function from_file()
    {
        if ($this->is_ajax()) {
            return null;
        }

        /**
         * @var $page array
         */
        $page = $this->has_cache();

        if (empty($page)) {
            return null;
        }

        $this->set_page_status($page['status_code'], $page['status']);
        $this->set_page_headers($page['headers']);
        $this->show_page_content($page['body']);

        return null;
    }

    /**
     * Установка кода ответа страницы (если требуется не 200)
     *
     * @param int $code
     * @param string $info
     */
    public function set_page_status($code = 0, $info = '')
    {
        if ($code != 200) {
            header('HTTP/1.0 ' . $info);
        }
    }

    /**
     * Установка заголовков страницы
     *
     * @param $headers array
     */
    public function set_page_headers($headers)
    {
        array_walk($headers, 'header');
    }

    /**
     * Показ контента страницы и прерывание работы скриптов
     *
     * @param $content string
     */
    public function show_page_content($content)
    {
        echo $content;
        exit;
    }


}