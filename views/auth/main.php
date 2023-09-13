<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 22.04.16
 * Time: 13:45
 */
?>

<!-- login form:  -->
<div class="page_wrap ng-scope" >
    <div class="login_page_wrap ng-scope">
        <div class="login_head_bg"></div>
        <div class="login_page">
            <div class="login_head_wrap clearfix">
                <div class="login_head_submit_wrap ng-scope">
                    <a class="login_head_submit_btn ng-scope" onclick="$('#submit').click()">
                        <span class="enter-button">Войти</span><i class="fa fa-angle-right icon"></i>
                    </a>
                </div>
                <a class="login_head_logo_link" href="https://telegram.org" target="_blank">
                    <i class="icon icon-tg-logo"></i><i class="icon icon-tg-title"></i>
                </a>
            </div>
            <div class="login_form_wrap">
                <form method="POST" name="login" id="login_form" action="<?php print(common_get_url_host()); ?>" class="ng-pristine ng-scope ng-invalid ng-invalid-required">
                  
                  <?php if(!empty($params['param_authorize_code']['redirect_view'])): ?>
                    <input type='hidden' name='redirect_view' value='<?php print($params['param_authorize_code']['redirect_view']); ?>' />
                  <?php endif; ?>
                  <?php if(!empty($params['param_authorize_code']['client_id'])): ?>
                    <input type='hidden' name='client_id' value='<?php print($params['param_authorize_code']['client_id']); ?>' />
                  <?php endif; ?>
                  <?php if(!empty($params['param_authorize_code']['response_type'])): ?>
                    <input type='hidden' name='response_type' value='<?php print($params['param_authorize_code']['response_type']); ?>' />
                  <?php endif; ?>
                  <?php if(!empty($params['param_authorize_code']['redirect_uri'])): ?>
                    <input type='hidden' name='redirect_uri' value='<?php print($params['param_authorize_code']['redirect_uri']); ?>' />
                  <?php endif; ?>
                  <?php if(!empty($params['param_authorize_code']['state'])): ?>
                    <input type='hidden' name='state' value='<?php print($params['param_authorize_code']['state']); ?>' />
                  <?php endif; ?>
                  
                  <h3 class="login_form_head">Авторизация</h3>
                    <p class="login_form_lead">Пожалуйста, введите ваш логин и пароль.</p>
                    <div class="login_phone_groups_wrap clearfix">
                        <div class="md-input-group">
                            <input autocomplete="off" type="text" id="login" placeholder="Логин" class="md-input animated" name="login" value="<?php echo (!empty($post['login']) ? $post['login'] : '')?>">
                        </div>
                        <div class="md-input-group">
                            <input id="password" autocomplete="off" class="md-input animated" type="password" placeholder="Пароль" name="password" value="<?php echo (!empty($post['password']) ? $post['password'] : '')?>">
                        </div>
                        <input type="submit" id="submit" class="hidden" value="submit">
                    </div>
                </form>
                <div class="login_form_messaging ng-hide">
                    <?php

                    if(!empty($params['error']))
                        echo common_showError($params['error'])

                    ?>
                </div>
            </div>
            <div>
                <div class="login_footer_wrap ng-scope">
                    <p>Добро пожаловать в Dashboard компании Зионек.</p>
                    <a class="logo_footer_learn_more_link" href="">Подробнее</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /login form:  -->