$(function() {
  $(document).on("pageInit", function() {
	
	$("#picker").cityPicker({
		toolbarTemplate: '<header class="bar bar-nav">\
		<button class="button button-link pull-right close-picker">确定</button>\
		<h1 class="title">所在城市</h1>\
		</header>'
	});
	
    $("#picker-name").picker({
      toolbarTemplate: '<header class="bar bar-nav">\
      <button class="button button-link pull-right close-picker">确定</button>\
      <h1 class="title">请选择您的职称</h1>\
      </header>',
      cols: [
        {
          textAlign: 'center',
          values: ['职称1', '职称2', '职称3']
        }
      ]
    });
	
	$("#picker-name1").picker({
      toolbarTemplate: '<header class="bar bar-nav">\
      <button class="button button-link pull-right close-picker">确定</button>\
      <h1 class="title">请选择科室名称</h1>\
      </header>',
      cols: [
        {
          textAlign: 'center',
          values: ['科室名称1', '科室名称2', '科室名称3']
        }
      ]
    });
	
	
  });
  $.init();
});
