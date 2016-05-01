$('.dropdown-toggle').dropdown();

console.log(document.cookie);

// http://stackoverflow.com/questions/9127498/how-to-perform-a-real-time-search-and-filter-on-a-html-table
var $rows = $('.table tr');
$('#search').keyup(function() {
    var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

    $rows.show().filter(function() {
        var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
        return !~text.indexOf(val);
    }).hide();
});

function showPassed(){
  $rows.show().filter(function() {
      var text = $(this).find('.outcome').text().replace(/\s+/g, ' ').toLowerCase();
      return !~text.indexOf('pass');
  }).hide();
}

function showFailed(){
  $rows.show().filter(function() {
      var text = $(this).find('.outcome').text().replace(/\s+/g, ' ').toLowerCase();
      return !~text.indexOf('fail');
  }).hide();
}

function reset(){
  $rows.show();
}

function collapse(){
  $('#console').collapse('toggle');
}
