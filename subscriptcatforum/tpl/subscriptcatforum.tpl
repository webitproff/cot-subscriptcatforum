<!-- BEGIN: MAIN -->
<div class="uk-section uk-background-muted uk-padding-y-10" uk-height-viewport="expand: true">
  <div class="uk-container">
    <div class="uk-margin-bottom">
      <h3>{PHP.L.subscriptcatforum_choose_forums}</h3>
    </div>
	<div class="uk-margin-bottom">

	{FILE "{PHP.cfg.themes_dir}/{PHP.usr.theme}/warnings.tpl"}
	</div>
    <div class="uk-card uk-card-body uk-background-default uk-border-rounded uk-margin-bottom">
		<form method="post" action="{URL}"> <!-- {URL} заменяется на текущий URL страницы -->
			<div class="categories">
				<ul class="uk-list">
					<!-- BEGIN: CATEGORY -->
					<li>
						<input type="checkbox" class="uk-checkbox" name="selected_categories[]" value="{CATEGORY_CODE}" {IS_SELECTED} />
						[{CATEGORY_ID}] {CATEGORY_TITLE}
					</li>
					<!-- END: CATEGORY -->
				</ul>
			</div>
			<button class="uk-button uk-button-primary" type="submit">Сохранить подписки</button>
		</form>
    </div>
  </div>
</div>
<!-- END: MAIN -->
 