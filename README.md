
# SubscriptCatForum Plugin for Cotonti CMF

User subscription to forum category updates

## 1. Functional Overview and Purpose

**SubscriptCatForum** is a plugin for **Cotonti CMF** that implements a system allowing users to subscribe to forum categories. After subscribing, the user receives email notifications when new events appear in the selected sections.

The main purpose of the plugin is to simplify tracking activity on the forum. Users do not need to regularly check the sections they are interested in: the system automatically sends notifications about new events.

### Main Features

#### Subscription to forum categories

The user can select one or several forum categories and subscribe to their updates. Subscription management is performed through a separate plugin page.

After subscribing, the user will receive notifications about all new events in the selected sections.

#### Email notifications

When new events appear in a category, the plugin sends an email notification to subscribers. The message contains:

- topic title  
- section title  
- name of the message author  
- text of the latest message (without HTML)  
- link to the topic  
- link to the specific post  

#### Tracked events

The plugin reacts to forum events through the Cotonti hook system. Notifications are sent for the following actions:

1. creation of a new topic in a category  
2. creation of a new post in a topic  
3. editing a message  
4. moving a topic between categories (handled in the notification logic)

#### User subscription management

The user can:

- subscribe to categories  
- modify the list of subscriptions  
- completely remove subscriptions  

This is done through a separate plugin page available via the user account menu.

#### Administrative subscription management

The site administrator can:

- view subscribers of each category  
- add a user to a subscription  
- remove a user from a subscription  

The administrative interface displays a list of forum categories and the users subscribed to them.

### Advantages of using

1. The user follows only the sections they are interested in.  
2. There is no need to subscribe to each topic individually.  
3. The chance of missing new discussions is reduced.  
4. The administrator can centrally manage subscriptions.

### Current limitations

At the moment of implementation the plugin has the following limitations:

- notifications are sent immediately when an event occurs (without cron)  
- pagination is not implemented  
- debug logging is enabled  

The plugin is intended for use on **small Cotonti forums**.

### Requirements

The plugin was tested in the following configuration:

- Cotonti CMF (latest version from GitHub as of 25-02-2025)  
- PHP 8.4  
- MySQL 8.4  

---

# 2. Technical part (plugin operation)

The plugin uses the **Cotonti hook system**, which allows it to react to forum events and send notifications.

## Plugin architecture

Main components:

- subscription table  
- forum event hooks  
- standalone subscription management page  
- administrative interface  
- subscription management functions  

---

## Subscription table

The plugin creates a separate table:

```sql
CREATE TABLE IF NOT EXISTS `cot_subscriptcatforum` (
`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`user_id` INT UNSIGNED NOT NULL,
`cat_forum_subs` text NOT NULL,
`created_at` INT UNSIGNED NOT NULL DEFAULT 0
);
````

Fields:

| field          | description                |
| -------------- | -------------------------- |
| id             | unique record identifier   |
| user_id        | user ID                    |
| cat_forum_subs | forum category code        |
| created_at     | subscription creation time |

---

# Forum hooks

The plugin reacts to several forum events.

## 1. New post

Hook:

```php
Hooks=forums.posts.newpost.done
```

File:

```
subscriptcatforum.forums.posts.newpost.done.php
```

Main actions:

1. determine the topic ID and category code
2. retrieve the latest post
3. find subscribers of the category
4. compose the email
5. send notifications

Retrieving subscribers:

```php
$subscribers = $db->query("
    SELECT u.user_email, u.user_id 
    FROM $db_subscriptcatforum s
    JOIN $db_users u ON s.user_id = u.user_id
    WHERE s.cat_forum_subs = ? 
    AND u.user_id != ?", [$section_code, $post_author_id])->fetchAll();
```

The post author is excluded from the recipients list.

---

## 2. Creating a new topic

Hook:

```php
Hooks=forums.newtopic.newtopic.done
```

File:

```
subscriptcatforum.forums.newtopic.newtopic.done.php
```

Algorithm:

1. determine the topic ID
2. retrieve the topic title and category
3. obtain the first post data
4. compose the email
5. send notifications to category subscribers

---

## 3. Editing a post

Hook:

```php
Hooks=forums.editpost.update.done
```

File:

```
subscriptcatforum.forums.editpost.update.done.php
```

This handler:

* receives the category
* finds subscribers
* retrieves the updated post
* sends an edit notification

---

## Link generation

Links to the topic and post are generated using standard Cotonti functions:

```php
$topic_url = cot_url('forums', 'm=posts&q=' . $topic_id);
$post_url = cot_url('forums', "m=posts&id=" . $post_id . "&n=last", "#bottom");
```

---

## Email sending

The built-in Cotonti function is used:

```php
cot_mail($user_email, $subject, $message);
```

---

## Logging

File logging is used for debugging:

```php
function log_to_file_newpostdone($message) {
$logfile = __DIR__ . '/subscription_debug.log';
$date = date('Y-m-d H:i:s');
file_put_contents($logfile, "[$date] $message\n", FILE_APPEND);
}
```

The log records:

* handler start
* request parameters
* found subscribers
* sent emails

---

## Subscription management functions

File:

```
subscriptcatforum.functions.php
```

Main functions:

### Subscription check

```php
function check_existing_subscription($user_id, $forum_id) {
return $db->query("SELECT COUNT(*) FROM $db_subscriptcatforum WHERE user_id = ? AND cat_forum_subs = ?", [$user_id, $forum_id])->fetchColumn() > 0;
}
```

### User subscription

```php
function subscribe_user($user_id, $forum_id) {
$db->query("INSERT INTO $db_subscriptcatforum (user_id, cat_forum_subs, created_at) VALUES (?, ?, ?)", [$user_id, $forum_id, time()]);
}
```

### User unsubscription

```php
function unsubscribe_user($user_id, $forum_id) {
$db->query("DELETE FROM $db_subscriptcatforum WHERE user_id = ? AND cat_forum_subs = ?", [$user_id, $forum_id]);
}
```

---

# 3. Installation, configuration and operation

## Plugin installation

1. Copy the plugin folder

```
subscriptcatforum
```

to the directory

```
/plugins/
```

2. Open the Cotonti administrator panel.
3. Find the **SubscriptCatForum** plugin.
4. Install it.

During installation the table is automatically created:

```
cot_subscriptcatforum
```

---

## Adding a link to the user menu

To access the subscription management page you need to add a link to the theme template.

Example fragment:

```html
<!-- IF {PHP|cot_plugins_active('subscriptcatforum')} -->
<li><a href="{PHP|cot_url('subscriptcatforum')}">{PHP.L.subscriptcatforum_title_link}</a></li>
<!-- ENDIF -->
```

The link is usually placed in the **user account menu**.

---

## Subscription management page

The plugin page displays:

* list of forum categories
* category hierarchy
* selection checkboxes

Example HTML structure:

```html
<input type="checkbox" 
      class="form-check-input"
      name="selected_categories[]" 
      value="{CATEGORY_CODE}"
      {IS_SELECTED}>
```

After submitting the form:

1. old user subscriptions are removed
2. selected categories are written to the table

```php
$db->query("DELETE FROM $db_subscriptcatforum WHERE user_id = ?", [$usr['id']]);
```

Then new records are added.

---

## Subscription administration

The administrator can manage subscriptions via the tool:

```
admin → tools → subscriptcatforum
```

The interface displays:

* forum categories
* list of subscribers
* form for adding a user

Adding a user:

```php
$db->insert($db_x . "subscriptcatforum", [
   'user_id' => $user_id,
   'cat_forum_subs' => $category_id,
   'created_at' => time()
]);
```

Removing a user:

```php
$db->delete($db_x . "subscriptcatforum", "user_id = ? AND cat_forum_subs = ?", [$user_id, $category_id]);
```

---

## Operation

The plugin starts working immediately after installation.

Notifications are automatically sent when:

* a topic is created
* a post is published
* a message is edited

All events are recorded in logs, which allows diagnostics to be performed.


[**Plugin support topic**](https://abuyfile.com/ru/forums/cotonti/custom/plugs/topic106)

**Download the plugin**
[https://github.com/webitproff/cot-subscriptcatforum](https://github.com/webitproff/cot-subscriptcatforum)


[**More extentions for Cotonti on marketplace**](https://abuyfile.com/en/market/cotonti)


___

<p># cot-subscriptcatforum<br />
Плагин &laquo;SubscriptCatForum&raquo; для CMS Cotonti.<br />
Плагин для подписки на уведомления о новых событиях (новая тема, пост в теме, тема перемещена) на форумах по категориям.</p>

<h2>Описание плагина SubscriptCatForum</h2>

<p>Плагин SubscriptCatForum позволяет пользователям подписываться на форумы по категориям и получать уведомления на почту о новых в этих разделах.</p>

<p>&nbsp;</p>
<img src="https://github.com/webitproff/cot-subscriptcatforum/blob/main/subscriptcatforum.png" alt="SubscriptCatForum" >

<h3>Требования и рекомендации:</h3>

<p>Тестировалось на актуальной версии Cotonti CMF из репозитория на GitHub по состоянию на 25-02-2025<br />
PHP 7.4<br />
MySQL 5.7</p>

<p><strong>для небольших форумов на сайтах&nbsp; Cotonti</strong></p>

<h2>Основной функционал:</h2>

<p>&nbsp;&nbsp;&nbsp; Подписка на категории форумов: Пользователи могут подписываться на определённые категории форума. Это означает, что они будут получать уведомления о новых событиях в этих категориях.<br />
&nbsp;&nbsp;&nbsp; Когда появляется новый пост в теме, пользователи, подписанные на категорию, получают уведомление на свою электронную почту. Уведомления похожи и примерно одинаково содержат:<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Название темы<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Имя автора поста<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Текст последнего поста (без HTML)<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Ссылку на саму тему и на конкретный пост,<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; и т.д.<br />
&nbsp;&nbsp;&nbsp; Возможность отключить уведомления: Если пользователь не хочет получать уведомления, он может отменить подписку на категорию на страница выбора таких категорий, по ссылке в личном кабинете.</p>

<h3><br />
Такими новыми событиями в том или ином разделе форума являются:</h3>

<p>1. Создание новой тему в категории,<br />
2. Перемещение темы в другую категорию,<br />
3. Обновление темы в категории<br />
4. Создание нового сообщения в той или иной теме конкретной категории</p>

<h2><br />
Установка плагина:</h2>

<p>1. папку subscriptcatforum закачать в папку с плагинами.</p>

<p>2. в админке найти плагин SubscriptCatForum и установить его.<br />
будет создана своя таблица со своими полями, смотрите setup/subscriptcatforum.install.sql</p>

<p>3. Открыть свою тему сайта и в нужном шаблоне, в меню личного кабинета пользователя, например перед ссылкой выхода с сайта, добавить ссылку на страницу подписок на категории, таким фрагментом кода:</p>

<p>&nbsp;</p>

<pre class="brush:xml;">
    &lt;!-- IF {PHP|cot_plugins_active(&#39;subscriptcatforum&#39;)} --&gt;
    &lt;li&gt;&lt;a class=&quot;uk-link-heading&quot; href=&quot;{PHP|cot_url(&#39;subscriptcatforum&#39;)}&quot;&gt;{PHP.L.subscriptcatforum_title_link}&lt;/a&gt;&lt;/li&gt;
    &lt;!-- ENDIF --&gt;</pre>

<p>&nbsp;</p>

<p>Администратор может в админке по id пользователя добавлять его в подписку на категорию или же удалять.</p>

<p>Внимание, включено логирование отладочной информации, - удалить можно комментированием соответствующих строк.</p>

<h4>Преимущества плагина:</h4>

<p>1. Подписался на категорию, которая интересует и не нужно каждый раз заходить и смотреть было ли что-то новое в интересующем разделе.<br />
2. Не нужно подписываться на каждую конкретную тему форума, которые часто теряются среди других топиков раздела.</p>

<p>&nbsp;</p>

<h4>Недостатки плагина:</h4>

<p>1. пока не реализована возможность отправки уведомлений через планировщик задач (cron).</p>

<p>2. нет пагинации пока.<br />
3. плагин только &quot;родился&quot;, так что могут быть ошибки или иные &quot;капризы&quot;</p>

<p><br />
<a href="https://abuyfile.com/ru/forums/cotonti/custom/plugs/topic106"><strong>Тема поддержки плагина</strong></a>, вопросы, обсуждения и предложения</p>

<p><br />
Скачать плагин <a href="https://github.com/webitproff/cot-subscriptcatforum">https://github.com/webitproff/cot-subscriptcatforum</a><br />
&nbsp;&nbsp; &nbsp;<br />
&nbsp;&nbsp; &nbsp;</p>

<p>&nbsp;</p>
