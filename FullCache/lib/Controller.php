<?php

/**
 * Class Component_FullCache_Controller
 */
class Component_FullCache_Controller extends CMS_Controller implements Core_ModuleInterface
{
    /**
     * Обработка ajax-запроса с массивом требующих загрузки вставок (insertions).
     * Этот массив приходит из POST в виде JSON-строки.
     * После декодирования массив должен иметь структуру ["Строка insertion", "Строка insertion 2", ... , "Строка insertion N"]
     *
     * После декодирования запускается процесс парсинга вставок стандартными средствами CMS
     * Результатом работы функции является массив массивов подготовленных к js выводу вставок в определенном фомате
     *
     * Формат выходных данных [[вставка], ... , [вставка N]],
     * где массив одной вставки иммет структуру [name = строка вызова, value = отрендеренный контент]
     *
     * @return array
     */
    public function parse_insertions()
    {
        $ajax_insertions = $this->request['ajax_load_insertions'];

        if (empty($ajax_insertions)) {
            return [];
        }

        return array_map([$this, 'parse_insertion'], array_unique(json_decode($ajax_insertions, true)));
    }

    /**
     * Обработка одного insertion для последующей вставки в ожидающий блок
     *
     * @param $name string
     * @return array
     */
    protected function parse_insertion($name)
    {
        return [
            'name' => $name,
            'value' => CMS::process_insertions($name),
        ];
    }

    /**
     * Формирование JSON ответа с подготовленными insertions
     *
     * @return Net_HTTP_Response
     */
    public function index()
    {
        return $this->json_response($this->parse_insertions());
    }
}