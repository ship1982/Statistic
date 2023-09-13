<?php
	session_start();
	common_setAloneView('statistic/inc/navbar');
?>

<style>
  #background{
      position: absolute;
      height: 50%;
      background-color: #4285F4;
      width: 100%;
      z-index: -2;
      font-size: 20px;
      line-height: 64px;
      color: #ffffff;
  }
  article{
      position: relative;
      width: 771px;
      min-height: 350px;
      top: 75px;
      margin: 0 auto;
      background-color: #FFFFFF;
      z-index: 1;
      font-size: 13px;
      color: #212121;
      font-family: Roboto, "Helvetica Neue", Helvetica, sans-serif;
  }
  article h2{
      padding: 5px;
      color: #aaa;
  }
  section{
      margin:45px;
  }
  section:hover{
      /*
      -webkit-box-shadow: 0 15px 10px -10px rgba(0, 0, 0, 0.5), 0 1px 0px rgba(0, 0, 0, 0.3); 
      -moz-box-shadow: 0 15px 10px -10px rgba(0, 0, 0, 0.5), 0 1px 0px rgba(0, 0, 0, 0.3); 
      box-shadow: 0 15px 10px -10px rgba(0, 0, 0, 0.3), 0 1px 0px rgba(0, 0, 0, 0.1);
			*/
  }
  #header_h1{
      text-align: center;
  }
	.pic {
		float: left; /* Обтекание картинки текстом */
	}
	.text {
		margin-left: 145px; /* Отступ от левого края */
	}
</style>
<h1 id="header_h1">Инструкции к API</h1>
<div id="background">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cправка - API</div>
<article>
  <hr />
  <section>
		<div class="pic">
			<a href="../../api_help/api_sequencer.get.zip" style="text-decoration: none;">
				<img src="../../bundles/img/search.png" height="100px" width="100px" border="0" alt="Получить инструкцию по API api_sequencer.get" title="Получить инструкцию по API api_sequencer.get" />
				<br />
				<b>api_sequencer.get</b>
			</a>
		</div>
		<p class="text">
			API для выбора информации по utm меткам.
		</p>
	</section>
	<br />
	<hr />
	<section>
		<div class="pic">
			<a href="../../api_help/condition_user_property.zip" style="text-decoration: none;">
				<img src="../../bundles/img/search.png" height="100px" width="100px" border="0" alt="Получить инструкцию по API api_sequencer.get_user_property" title="Получить инструкцию по API api_sequencer.get_user_property" />
				<br />
				<b>api_sequencer<br />.get_user_property</b>
			</a>
		</div>
		<p class="text">
			API для работы с данными по пользователю.
		</p>
  </section>
	<br />
	<hr />
	<section>
		<div class="pic">
			<a href="../../api_help/repeate_actions.get.zip" style="text-decoration: none;">
				<img src="../../bundles/img/search.png" height="100px" width="100px" border="0" alt="Получить инструкцию по API repeate_actions.get" title="Получить инструкцию по API repeate_actions.get" />
				<br />
				<b>repeate_actions.get</b>
			</a>
		</div>
		<p class="text">
			API для определения Бот/Не бот.
		</p>
 </section>
</article>