<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=no">
<meta name="robots" content="noindex,nofollow">
<title>RSS取込</title>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="https://suteki-life.style/mt-static/data-api/v4/js/mt-data-api.js"></script>

<?php
$xml = simplexml_load_file("http://suteki:oeu30Ws@suteki-life.leaf-hide.jp/rss2all.xml");
foreach($xml->channel->item as $item){
  $x = array();
  $x['title'] = (string)$item->title;
  $x['link'] = (string)$item->link;
  $x['description_summary'] = (string)$item->description_summary;
  $x['description_all'] = (string)$item->description_all;
  $x['image_files'] = (string)$item->image_files;
  $x['showDate'] = (string)$item->showDate;
  $x['hash'] = (string)$item->hash;
  $x['tag'] = (string)$item->tag;
  $x['location'] = (string)$item->location;
  $data[] = $x;
}
$json_string = json_encode($data);
?>
<script>

var ajax_get_entry_url = "https://suteki-life.style/mt/mt-data-api.cgi/v4/sites/1/entries";
var ajax_set_asset_url = "https://suteki-life.style/mt/mt-data-api.cgi/v4/assets/upload?site_id=6";
var ajax_set_entry_url = "https://suteki-life.style/mt/mt-data-api.cgi/v4/sites/1/entries";

/* --------------------
変数
-------------------- */
var api_key = 'AIzaSyANezj0DHE5p-Vn4ZmU7GjBX8kuRIxL9yI';
var site_id = 1;
var post_arr = [];
var rss_arr = <?php echo $json_string; ?>;
var api = new MT.DataAPI({
  baseUrl:  "https://suteki-life.style/mt/mt-data-api.cgi",
  clientId: "your-client-id"
});
var accessToken = '';


$(document).ready(function(){
  $("input").prop('disabled', true);

  api.authenticate({
    username: "x6rgcnng",
    password: "u3hwa7c6",
    remember: true
  }, function(response) {
    if (response.error) {
      // エラー処理
      putLog('認証失敗');
      return;
    }

    // レスポンスデータを使った処理
    api.storeTokenData(response);
    console.log(response);
    accessToken = "MTAuth accessToken=" + response.accessToken;

    var params = {
      limit: 50,
      fields:'id,customFields',
    };

    putLog('「既存データ」取得 開始');
    api.listEntries(site_id, params, function(response) {
      //console.log(response);
      if (response.error) {
        // エラー処理
        putLog('「既存データ」取得 失敗');
        return;
      }

      var data = response.items;

      place_arr = [];
      for(var i = 0; i < data.length; i++) {

        for(var k = 0; k < data[i].customFields.length; k++) {
          if (data[i].customFields[k].basename == 'hash') {
            data[i].hash = (data[i].customFields[k].value) ? data[i].customFields[k].value : '';
          }
        }
        post_arr.push({'id': data[i].id, 'hash': data[i].hash});
      }

      putLog('「既存データ」取得 成功');
      console.log(post_arr);

      $("input").prop('disabled', false);
    });
  });
});


function putLog(str) {
  $('#logger textarea').val(function(i, text) {
    return text + '\n' + str;
  });
}


async function postDatas() {
  for (var i = 0; i < rss_arr.length; i++) {
    var res = await postData(i);
  }
}

function postData(i){

  var rss = rss_arr[i];
  console.log(rss);

  // 既存データ確認
  var flg_put = false;
  var entry_id = '';
  for(var k = 0; k < post_arr.length; k++) {
    if (post_arr[k].hash == rss.hash) flg_put = true;
  }

  if (flg_put) {
    putLog('「' + rss.title + '」はすでに登録済みです。スキップします。');
  } else {
    // CREATE
    putLog('「' + rss.image_files + '」アセット登録 開始');

    // アセットの登録

    var p = "https://keygeneration.site/suteki/?url=" + rss.image_files;
    var fileNameIndex = rss.image_files.lastIndexOf("/") + 1;
    var filename = rss.image_files.substr(fileNameIndex);

    getURL(p).then(function onFulfilled(value) {
      console.log(value);

      putLog('「' + rss.image_files + '」アセット取得 完了');

      var fd = new FormData();
      fd.append('site_id', site_id);
      fd.append('file', value, filename);
      buffer = fd;

      $.ajax({
        url: ajax_set_asset_url,
        type: "POST",
        dataType: "json",
        headers: {
            'X-MT-Authorization': accessToken
        },
        data: fd,
        processData: false,
        contentType: false
      }).done(function(data){
        putLog('「' + rss.image_files + '」アセット登録 完了');
        console.log(data);

        rss_tags_arr = rss.tag.split(',');
        rss_tags_arr = rss_tags_arr.filter(v => v);
        rss_tags_str = '"' + rss_tags_arr.join('","') + '"';

        rss.description_all = rss.description_all.replace( rss.image_files, data.url );


        var formatISO8601 = function (date) {
          console.log(date);
          addZero = function(time) {
            return ("0" + time).slice(-2);
          }
          return ISO8601Date = date.getFullYear() + "-" +
                              addZero(date.getMonth() + 1) + "-" +
                              addZero(date.getDate()) +
                              "T" +
                              addZero(date.getHours()) + ":" +
                              addZero(date.getMinutes()) + ":" +
                              addZero(date.getSeconds()) + "+" +
                              addZero(date.getTimezoneOffset()/(-60)) + ":00";
        };

        var iso8601 = formatISO8601(new Date(rss.showDate));
        console.log(iso8601);

        var post_data = {
          'title': rss.title,
          'body': rss.description_all,
          'excerpt': rss.description_summary,
          'status': 'Draft',
          'assets': [
            { 'id' : data.id }
          ],
          'customFields' : [
            {'basename' : "hash", 'value' : rss.hash}
          ],
          'tags': rss_tags_arr,
          'date': iso8601,
          'createdDate': iso8601,
          'modifiedDate': iso8601
        };
        console.log(post_data);

        putLog('「'  + post_data.title + '」記事作成 開始');
        api.createEntry(site_id, post_data, function(response) {
          console.log(response);
          if (response.error) {
            // エラー処理
            putLog('「'  + post_data.title + '」記事作成 失敗');
            return;
          }
          putLog('「'  + post_data.title + '」記事作成 完了');
          return response;
        });


      }).fail(function(data){
        putLog('「' + rss.image_files + '」アセット登録 失敗');
        console.log(data);
            return;
      });
    }).catch(function onRejected(error) {
      putLog('「' + rss.image_files + '」アセット取得 失敗');
      console.error(error);
            return;
    });
  }
}


function getURL(URL) {
  return new Promise((resolve, reject) => {
    let req = new XMLHttpRequest();
    req.withCredentials = true;
    req.open('GET', URL, true, 'suteki', 'oeu30Ws');
    req.onload = function() {
      if (req.status === 200) {
        resolve(req.response);
      } else {
        reject(new Error(req.statusText));
      }
    };
    req.onerror = function() {
      reject(new Error(req.statusText));
    };
    req.responseType = 'blob';
    req.send();
  });
}




/* - - - - - - - - - -
var entryData = {
  'title': タイトル,
  'body': 本文,
  'categories': [
    { 'id' : 1 },
    { 'id' : 2 }
  ]
};
assets: [
    { id: アイテム1のID },
    { id: アイテム2のID },
    ・・・
    { id: アイテムnのID }
  ],
entry={
"excerpt" : "We are excited to announce that Six Apar-",
"status" : "Publish",
"allowComments" : true,
"body" : "¥u003cp¥u003e¥u003cspan¥u003eWe are excited to announce that Six Apart has acquired Topics, a dynamic online publishing product. This offering will provide Six Apart customers with an easy and cost-effective way to adapt existing content to evolving digital platforms.¥u003c/span¥u003e¥u003c/p¥u003e¥n¥u003cp¥u003e¥u003cspan¥u003eThis new product will save Six Apart customers a significant amount of time and money by allowing users to upgrade their websites and applications without migrating from their current content management systems. Clients who need to scale large amounts of data or even revamp a website on an entirely new platform can now achieve these changes with minimal effort.¥u003c/span¥u003e¥u003c/p¥u003e¥n¥u003cp¥u003e¥u003cspan¥u003eSix Apart customers will benefit not only from saved time and money, but also from ease of use. Topics does not have a user interface, so there is no new software to learn. Instead, it exists as a middle layer between the data library and the published page - automatically gathering, organizing and redistributing data.¥u003c/span¥u003e¥u003c/p¥u003e",
"keywords" : "",
"allowTrackbacks" : false,
"basename" : "six_apart_acquires_topics_server_to_simplify_site_upgrades",
"title" : "Six Apart Acquires Topics Server to Simplify Site Upgrades",
"more" : "",
"customFields" : [
  {
    "basename" : "place",
    "value" : "New York City"
  },
  {
    "basename" : "agenda",
    "value" : "Movable Type¥nTopics"
  }
  ]
}
- - - - - - - - - - */
  </script>

</head>
<body>


  <div id="canvas"></div>


  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-12">
        <h2>RSS取り込み</h2><input type="button" onclick="postDatas()" value="RSSを取り込む" />
        <div id="logger">
          <textarea style="width: 100%; height:30em;"></textarea>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
