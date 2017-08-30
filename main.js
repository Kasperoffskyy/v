//Здесь будет записана текущая страница
var currentPage = null;
var _csrf = null;
//Отслеживаем измененияя hash, чтобы переключать страницы в route
window.addEventListener("hashchange",function(event){

    if (currentPage != currentHash()){
      route(currentHash());
    }

});

//Текущий hash
var currentHash = function() {
  return location.hash.replace(/^#/, '')
}

//Главная по умолчанию - страница all_orders
var hash = currentHash() || 'all_orders';


//Навигация
document.body.querySelector('.head-content').addEventListener('click', function(target){

  if (target.target.classList.contains('head-link')){
    route(target.target.dataset.route);
  }

});


//Загрузка страниц
var reTryTime = null;
var loadScreen = null;
var inProcess = false;

function route(page, noreload){

  if (reTryTime){
    clearTimeout(reTryTime);
  }

  loadScreen = setTimeout(function(){document.body.querySelector('#preload').innerHTML = '<div class="loading">Загрузка...</div>'}, 777);

  var xhr = new XMLHttpRequest();
  xhr.open('GET', '/main.php?page=' + page + '&_=' + new Date().getTime());
  xhr.onload = function (e) {

      errAlert(null, true)

      inProcess = false;

      if (xhr.readyState == 4 && xhr.status == 200) {

        clearTimeout(loadScreen);
        inHtml(page, JSON.parse(xhr.responseText), xhr.status, noreload);

      }else{

        if (xhr.status != 404) {
          reTryTime = setTimeout(function(){route(page)}, 2500);
        }else{

          clearTimeout(loadScreen);
          inHtml(page, JSON.parse(xhr.responseText), xhr.status);
        }
      }

  };
  xhr.send(null);

}

//Обработка содержания страниц
function inHtml(page, data, status, noreload){

  window.location.hash = page;
  currentPage = page;
  _csrf = data._csrf;

  if (status == 200){
    authozire(data.auth);
  }else{
    document.title = 'Ошибка';
    document.body.querySelector('#title').innerHTML = 'Ошибка';
    document.body.querySelector('#preload').innerHTML = 'Ничего не найдено';
    return;
  }

  if (data.action == 'back'){
    route('all_orders');
  }

  if (!noreload){
    document.title = data.title;
    document.body.querySelector('#title').innerHTML = data.title;
    document.body.querySelector('#show-more').innerHTML = "";

    var content = '';
    var topNav = '';

    page = page.split('&')[0];

    //Вход
    if (page == 'login'){

      content = '<div class="form-login">' +
          '<form id="login" onSubmit="login();return false;">' +
            '<div class="input-text1">Ваш E-mail:</div>' +
            '<input type="text" name="email" id="email" class="input1" placeholder="E-mail...">' +
            '<div class="input-text1">Ваш пароль:</div>' +
            '<input type="password" name="password" id="password" class="input1" placeholder="Пароль...">' +
            '<div class="inprocess">Пожалуйста, подождите...</div>' +
            '<button class="button1 buttons-proc">Авторизоваться</button>' +
          '</form>' +
        '</div>';

    }

    //Регистрация
    if (page == 'register'){

      content = '<div class="form-register">' +
          '<form id="register" onSubmit="register();return false;">' +
          '<input type="hidden" name="type" id="type">' +
          '<div class="input-text1">Ваше имя:</div>' +
          '<input type="text" name="name" id="name" class="input1" placeholder="Ваше имя...">' +
          '<div class="input-text1">Ваш E-mail:</div>' +
          '<input type="text" name="email" id="email" class="input1" placeholder="Ваш E-mail...">' +
          '<div class="input-text1">Придумайте пароль:</div>' +
          '<input type="password" name="password" id="pass1" class="input1" placeholder="Пароль...">' +
          '<div class="input-text1">Пароль еще раз:</div>' +
          '<input type="password" name="rpassword" id="pass2" class="input1" placeholder="Пароль еще раз...">' +
          '<div class="radio">' +
            '<div class="input-text1">Кто вы ?</div>' +
            '<div class="radio1" id="type1" onclick="whoIm(1);">Я заказчик</div> <div class="radio2" id="type2" onclick="whoIm(2);">Я исполнитель</div>' +
          '</div>' +
          '<div class="inprocess">Пожалуйста, подождите...</div>' +
          '<button class="button1 buttons-proc">Зарегистрироваться</button>' +
        '</form>' +
      '</div>';

    }

    //Новый заказ
    if (page == 'new_order'){

      content = '<div class="form-new-order">' +
          '<form id="new-order" onSubmit="neworder();return false;">' +
          '<div class="input-text1">Заголовок задания / предложения:</div>' +
          '<input type="text" name="title" id="title" class="input1" placeholder="Краткий заголовок с основной сутью дела">' +
          '<div class="input-text1">Подбробности:</div>' +
          '<textarea name="text" id="text" class="textarea1" placeholder="Что нужно делать?"></textarea>' +
          '<div class="input-text1">Стоимость выполнения:</div>' +
          '<input type="text" name="price" id="price" class="input2" value="0"><b>рублей</b>' +
          '<div class="inprocess">Пожалуйста, подождите...</div>' +
          '<button class="button1 buttons-proc">Опубликовать</button>' +
        '</form>'  +
      '</div>';

    }

    //Страница все заказы
    lastShowId = 0;
    if (page == 'all_orders' && data.content){

      if (data.auth){

            if (data.auth.type == 1){
              var my = "   <a class=\"show-orders\" href=\"javascript:route('all_orders&p=my');\">Показать только мои заказы</a> ";
            }
            if (data.auth.type == 2){
              var my  = "   <a class=\"show-orders\" href=\"javascript:route('all_orders&p=my');\">Показать в которых я исполнитель</a> ";
            }

          topNav = "    <a class=\"show-orders\" href=\"javascript:route('all_orders');\">Показать все заказы</a> " + my;
        }



      for (var i = 0; i < data.content.length; i++) {

        if (data.content[i].id >> lastShowId){
          lastShowId = data.content[i].id;
        }

        content += "<div class=\"block\" onclick=\"route('select_order&p=" + data.content[i].id + "');\"> <h2>" + data.content[i].title + "</h2> <p class=\"block-text\">" + data.content[i].text + "</p> <p class=\"order-price\"><font class=\"order-price-i\">Награда: </font> " + data.content[i].price + " <font class=\"order-price-i\">Р</font></p> </div>";
      }

      if (i > 24){
        document.body.querySelector('#show-more').innerHTML = "<a href=\"javascript:showMore();\" class=\"show-more\">Показать еще</a>";
      }

      content = topNav + content;

    }

    //Страница выбранного заказа
    if (!content && data.content){

      var performers = '';
      var performersList = '';

      var btns = " <div class=\"fl\"> <div class=\"btns-info1\">Только для исполнителей.</div> </div> ";

      if (data.auth && data.auth.id == data.content.user_id){

          btns = " <div class=\"fl\">   <div class=\"btns-info3\">Это Ваш заказ</div> </div>   ";

          if (data.content.performer){

            btns = "   <div class=\"fl\"> <div class=\"btns-info2\">Заказ выполнен</div>   </div>   ";
          }

        }

        if (data.auth && data.auth.type == 2){

          if (!data.content.exist){
            btns = "   <div class=\"fl\">   <button class=\"button1 fl\" onclick=\"takeOrder(" + data.content.id + ");\">Взять этот заказ</button> </div>   ";
          }else{
            btns = "   <div class=\"fl\"> <div class=\"btns-info2\">Заказчик должен утвердить Вас.</div>     </div> ";
          }

          if (data.content.performer == data.auth.id){
            btns = " <div class=\"fl\">   <div class=\"btns-info3\">Вас выбрали исполнителем</div>   </div> ";
          }

        }

        var panel = " <div class=\"order-panel\">   "  + btns +  " <div class=\"from\">ЗАКАЗЧИК: <b>" + data.content.name + "</b></div>   <div class=\"clear\"></div> </div>   ";

        if (data.content.performers && data.content.performers.length > 0 && !data.content.performer){

          for (var i = 0; i < data.content.performers.length; i++) {

            var perf = data.content.performers[i].info.split('/');

            performersList += " <div class=\"performers\" onclick=\"selectPerformer("+ perf[1] + ", '" + perf[0] + "', " + data.content.price + ", " + data.content.id + ");\"> " + decodeURIComponent(perf[0]) +" </div>";

          }

          performers = " <h2 style=\"margin-top:35px;\">Выберите исполнителя:</h2> " + performersList;

        }

        content = panel + "<div class=\"block\" style=\"cursor:default;\"> <h2>" + data.content.title + "</h2> <p class=\"block-text\">" + data.content.text + "</p> <p class=\"order-price\"><font class=\"order-price-i\">Награда: </font> " + data.content.price + " <font class=\"order-price-i\">Р</font></p> </div>" + performers;

    }

    if (!content){
      content = 'Похоже, здесь ничего нет.';
    }

   document.body.querySelector('#preload').innerHTML = content;
  }

}

//Первая загрузка страницы, если ничего не выбрано - загружается страница по умолчанию из var hash
route(hash);


//Функция, которая показывает нас, как авторизованного пользователя
//Вызывается при обращении к любой странице
function authozire(auth){

  var typeName ='';
  var userPanel = '';
  var userBalance = '';
  var upUserBalance = '';

  if (auth){

    var user = auth.name;
    var type = auth.type;
    var balance = auth.balance;

    if (type == 1){
       typeName = '<font style="color:red;">заказчик</font>';
       userPanel = '<a class="head-link head-link-red" data-route="new_order"> + Добавить свой</a>';
       upUserBalance = '(<a href="javascript:upBalance();">пополнить</a>)';
    }
    if (type == 2){
       typeName = '<font style="color:green;">исполнитель</font>';
    }

      var rounded = function(number){
        return + number.toFixed(2);
      }

       userBalance = '<div class="balance">Счет: <b>'+ rounded(parseFloat(balance)) +'</b> .руб '+ upUserBalance +'</div>';

    var logged = ''

      + '<div class="logged">Вы: '+ typeName +' <b>'+ user +'</b></div>'
      + '<a class="head-link2" href="javascript:logout();">Выход</a>' +

    '';

  }else{

    var logged = ''

      + '<a class="head-link" data-route="login">Вход</a>'
      + '<a class="head-link" data-route="register">Регистрация</a>' +

    '';

  }

  document.body.querySelector('#user-panel').innerHTML = userPanel;
  document.body.querySelector('#user-balance').innerHTML = userBalance;
  document.body.querySelector('#auth').innerHTML = logged;

}

//Создать новый заказ (для заказчика)
function neworder(){
  sendPost(serialize(document.querySelector('#new-order')), 'new_order');
}

//Взять заказ (для исполнителя)
function takeOrder(id){
  sendPost('take-order=' + id, 'order_action');
}

//Определить исполнителя (для заказчика)
function selectPerformer(id, name, price, order_id){
  if (confirm('Вы уверены, что хотите выбрать исполнителем: ' + decodeURIComponent(name) + ' (ему будут отправлены зарезервированные ' + price + 'р, минус процент системы, на счет)?')){
    sendPost('select-performer=' + id + '&order_id=' + order_id, 'order_action');
  }
}

//Запрос на регистрацию
function register(){
  sendPost(serialize(document.querySelector('#register')), 'register');
}

//Запрос на авторизацию
function login(){
  sendPost(serialize(document.querySelector('#login')), 'login');
}

//Запрос на выход
function logout(){
  sendPost('logout=1', 'logout');
}

//Запрос на demo пополнение счета
function upBalance(){

  var amount = prompt('Сколько денег добавить?');

  if (amount){
    sendPost('up_balance=' + amount, 'up_balance');
  }else{
    alert('Введите сумму!');
  }

}

//Подгрузка заказов
function showMore(){

  var xhr = new XMLHttpRequest();
  xhr.open('GET', '/main.php?page=show_more&p=' + lastShowId + '&_=' + new Date().getTime());
  xhr.onload = function (e) {

      if (xhr.readyState == 4 && xhr.status == 200) {

        data = JSON.parse(xhr.responseText);
        var content = '';

        if (data.content){
        for (var i = 0; i < data.content.length; i++) {

            lastShowId = data.content[i].id;

          content += "<div class=\"block\" onclick=\"route('select_order&p=" + data.content[i].id + "');\"> <h2>" + data.content[i].title + "</h2> <p class=\"block-text\">" + data.content[i].text + "</p> <p class=\"order-price\"><font class=\"order-price-i\">Награда: </font> " + data.content[i].price + " <font class=\"order-price-i\">Р</font></p> </div>";
        }

        var all_cont = document.querySelector('#preload');
        all_cont.innerHTML = all_cont.innerHTML + content;

      }

    }

  };
  xhr.send(null);

}

//Дейсвтия с полученными ответами
function actionPost(result){

  //Вход через форму входа или после регистрации
  if (result.action == 'auth'){
    authozire(result.auth);
    route('all_orders');
  }

  //Добавление нового заказа
  if (result.action == 'new-order'){
    authozire(result.auth);
    route('all_orders');
    succAlert('<b>Ваш заказ создан.</b>');
  }

  //На главную если полез, куда не следует
  if (result.action == 'back'){
    succAlert('<b>Недостаточно прав</b>');
    authozire(result.auth);
    route('all_orders');
  }

  //Счет заказчика пополнен
  if (result.action == 'up-balance-true'){
    alert('Будем считать, что Вы прошли процедуру оплаты.');
    succAlert('<b>DEMO счет успешно пополнен!</b>');
    route(currentPage, true);
  }

  //Счет заказчика не пополнен
  if (result.action == 'up-balance-false'){
    errAlert('<b>Некорректное значение пополнения счета, используйте числа</b>');
  }

  //Получилось взять заказ
  if (result.action == 'take-order-true'){
    alert('Для перевода денег, нужно подтверждение выполнения с аккаунта заказчика, который создал заказ');
    succAlert('<b><b>Вы взяли заказ!</b> Заказчик должен утвердить Вашу кандидатуру и перевести деньги!</b>');
    route(currentPage);
  }

  //Исполнитель выбран
  if (result.action == 'select-performer-true'){
    succAlert('<b>Исполнитель выбран. Исполнитель получил деньги. Этот заказ закрыт.</b>');
    authozire(result.auth);
    route(currentPage);
  }

  //Что то пошло не так с выбором исполнителя
  if (result.action == 'select-performer-true'){
    authozire(result.auth);
    errAlert('<b>Исполнитель не выбран. Попробуйте еще раз.</b>');
  }

  //Не получилось взять заказ
  if (result.action == 'take-order-false'){
    errAlert('<b>Этот заказ уже кто-то взял либо он не существует</b>');
  }

  //Выход
  if (result.action == 'logout'){
    route('all_orders');
  }

}

//Выбор Кто я?
function whoIm(type){

  document.getElementById('type').value = type;

  if (type == 1){
    document.getElementById("type1").className += " typeActive";
    document.getElementById("type2").className = " radio2";
  }
  if (type == 2){
    document.getElementById("type2").className += " typeActive";
    document.getElementById("type1").className = " radio2";
  }

}

//Просто аналог обычному alert для красоты
function succAlert(text, hide){

    if (hide){
      document.body.querySelector('.succAlert').style.display="none";
      return;
    }

    document.body.querySelector('.succAlert').innerHTML = text;
    document.body.querySelector('.succAlert').style.display="block";
    setTimeout(function(){document.body.querySelector('.succAlert').style.display="none"}, 10000);

}

hideErrAlert = false;
function errAlert(text, hide){

    if (hide){
      document.body.querySelector('.errAlert').style.display="none";
      return;
    }

    document.body.querySelector('.errAlert').innerHTML = text;
    document.body.querySelector('.errAlert').style.display="block";

    if (!hideErrAlert){
      setTimeout(function(){hideErrAlert = false; document.body.querySelector('.errAlert').style.display="none"}, 5000);
    }

    hideErrAlert = true;

}

//Отправка post запросов
function sendPost(params, page){

  if (inProcess){
    return;
  }

  errAlert(null, true);

  inProcess = true;

  succAlert('Пожалуйста, подождите...');

  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/main.php?page=' + page + '&_=' + new Date().getTime());
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function (e) {

      inProcess = false;
      succAlert(null, 1);

      if (xhr.readyState == 4 && xhr.status == 200) {
         actionPost(JSON.parse(xhr.responseText));
      }else{

        if (xhr.readyState == 4 && xhr.status == 403){

          if (xhr.responseText){
            var jsonErrors = JSON.parse(xhr.responseText);
            var errors = '';

            for (var i = 0; i < jsonErrors.length; i++) {
              errors = errors + '<p><b>' + jsonErrors[i] + '</b></p>';
            }

          }else{
            errors = 'Ошибка. Попробуйте снова.';
          }

          errAlert(errors);
        }else{
          errAlert('<p><b>Ошибка на сервере, попробуйте еще раз через несколько секунд.</b></p>');
        }

        return false;

      }

  };

  xhr.send(params + '&_csrf=' +_csrf);

}


// var lastPreload = null;
// if (currentHash() == 'all_orders'){
//
//
//   document.addEventListener("scroll", function (event) {
//      checkForNewDiv();
//   });
//
//   var checkForNewDiv = function() {
//      var lastDiv = document.querySelector("body");
//      var lastDivOffset = lastDiv.offsetTop + lastDiv.clientHeight;
//      var pageOffset = window.pageYOffset + window.innerHeight;
//
//      if(pageOffset > lastDivOffset - 250) {
//
//        if (lastPreload != pages){
//          lastPreload = pages;
//          route('all_orders&pg=' + pages, true);
//       }
//
//      }
//   };
//
//
// }







//Функция для сбора информации с форм
function serialize(form) {
    var field, s = [];
    if (typeof form == 'object' && form.nodeName == "FORM") {
        var len = form.elements.length;
        for (i=0; i<len; i++) {
            field = form.elements[i];
            if (field.name && !field.disabled && field.type != 'file' && field.type != 'reset' && field.type != 'submit' && field.type != 'button') {
                if (field.type == 'select-multiple') {
                    for (j=form.elements[i].options.length-1; j>=0; j--) {
                        if(field.options[j].selected)
                            s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[j].value);
                    }
                } else if ((field.type != 'checkbox' && field.type != 'radio') || field.checked) {
                    s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value);
                }
            }
        }
    }
    return s.join('&').replace(/%20/g, '+');
}
