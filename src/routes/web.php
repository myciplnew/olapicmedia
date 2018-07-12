<?php

Route::get("/test",function(){
	echo "test";
});

Route::get('/blukUpload/{count}/{user_id}', 'myciplnew\olapicmedia\OlapicmediaController@blukUpload');

//Route::get('newtimezones/{timezone}', 'myciplnew\olapicmedia\OlapicmediaController@index');

