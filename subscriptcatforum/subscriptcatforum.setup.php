<?php
/* ====================
[BEGIN_COT_EXT]
Code=subscriptcatforum
Name=SubscriptCatForum
Category=community-social
Description=Плагин для подписки на уведомления о новых событиях (новая тема, пост в теме, тема перемещена) на форумах по категориям.
Version=1.0.0
Date=2025-02-27
Author=webitproff
Copyright=Cotonti CMF
Notes=BSD License
SQL=
Auth_guests=
Lock_guests=12345A
Auth_members=RW
Lock_members=12345A
Recommends_modules=
Recommends_plugins=
Requires_modules=forums
Requires_plugins=
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
[END_COT_EXT_CONFIG]
==================== */

defined('COT_CODE') or die('Wrong URL');
/* 
enable_cron_mail=1:radio::0:уведомления будут отправляться только через cron
Разбор полей:

    Code: Уникальный код плагина, в данном случае subscriptcatforum.
    Name: Название плагина, например, SubscriptCatForum.
    Category: Категория, к которой относится плагин. Например, можно указать Forums, если плагин связан с форумами.
    Description: Описание плагина, например, Плагин для подписки на форумы по категориям и отправки уведомлений о новых постах.
    Version: Версия плагина, например, 1.0.0.
    Date: Дата выпуска текущей версии плагина, например, 2025-02-27.
    Author: Автор плагина. Здесь можно указать ваше имя или компанию.
    Copyright: Авторские права, например, ваше имя или название вашей компании.
    Notes: Лицензия плагина. В данном случае BSD License.
    SQL: Если плагин использует SQL-таблицы, то укажите путь к SQL-скрипту. Если нет, оставьте пустым.
    Auth_guests: Права доступа для гостей, например, R — доступ только для чтения.
    Lock_guests: Лок для гостей, например, 12345A — защищает от несанкционированного доступа.
    Auth_members: Права доступа для зарегистрированных пользователей, например, RW — чтение и запись.
    Lock_members: Лок для зарегистрированных пользователей, например, 12345A.
    Recommends_modules: Модули, которые рекомендуется использовать с плагином (если применимо).
    Recommends_plugins: Плагины, которые рекомендуется использовать с плагином (если применимо).
    Requires_modules: Модули, которые необходимы для работы плагина. В данном случае, forums, так как плагин работает с форумами.
    Requires_plugins: Плагины, которые необходимы для работы плагина (если применимо). Если нет, оставьте пустым.

 */