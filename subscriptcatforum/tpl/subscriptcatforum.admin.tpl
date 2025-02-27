<!-- BEGIN: MAIN -->
<h2>Категории форума и подписки пользователей</h2>
<div class="uk-card uk-card-default uk-card-small uk-card-body">
<table class="uk-table uk-table-striped">
  <thead>
    <tr>
      <th>Категория</th>
      <th>Подписчики</th>
      <th>Добавить пользователя</th>
    </tr>
  </thead>
  <tbody>
    <!-- BEGIN: CATEGORY -->
    <tr>
      <td>{CATEGORY_TITLE}</td>
      <td>
        <ul>
          <!-- BEGIN: SUBSCRIBERS -->
          <li>
          <div uk-grid>
            <div>
			
              {USER_NAME}
			 
            </div>
            <div>
		   <form method="post" action="{REMOVE_USER_FORM}" style="display:inline;">
		   <div class="uk-margin">
              <button class="uk-button uk-button-small uk-button-danger" type="submit">Удалить</button>
			   </div>
            </form>
            </div>
          </div>

          </li>
          <!-- END: SUBSCRIBERS -->
        </ul>
      </td>
      <td>
        <form method="post" action="{ADD_USER_FORM_ACTION}">
          <div uk-grid>
            <div>
              <div class="uk-margin">
                <input type="text" class="uk-input" name="user_id" placeholder="ID пользователя">
              </div>
            </div>
            <div>
              <button class="uk-button uk-button-primary" type="submit">Добавить</button>
            </div>
          </div>
        </form>
      </td>
    </tr>
    <!-- END: CATEGORY -->
  </tbody>
</table>
</div>
<!-- END: MAIN -->