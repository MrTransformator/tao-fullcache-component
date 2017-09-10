# tao-fullcache-component
Компонент для TAO.CMS, предназначенный для полностраничного кеширования страниц сайта с возможностью ajax-подгрузки определенных областей

Инициализация компонента производится в `index.php` файле строкой `Core::load('Component.FullCache');`

Вызов компонента осуществляется также в файле `index.php` командами
`include('../app/components/FullCache/FullCacheL.php');
 FullCacheL::instance()->from_file();
`
Вызов компонента должен быть расположен перед инициализацией CMS