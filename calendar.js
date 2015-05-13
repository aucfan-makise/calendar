(function(){
	$(function(){
		initializeMoveButton();
		appendComboBox();
		$.ajax({
			type: 'GET',
			url: 'calendar_table.php',
			dataType: 'html',
			success: function(data){
				$('#calendar').append(data);
				$.getScript('schedule.js');
			},
			error:function() {
				alert('Ajax error.');
			}
		});
		
		$('[name=selected_date_before]').click(function(){
			$.ajax({
				type: 'GET',
				url: 'calendar_table.php',
				dataType: 'html',
				data: {selected_date: $(this).val(),
					start_week_day : $('[name=start_week_day]').val()},
				success: function(data){
					if($('[name=selected_date_before]').val() != ''){
						$('#calendar').find('tr:gt(0)').remove();
						$('#calendar').append(data);
						var date = $('[name=selected_date_before]').val();
						changeMoveButtonValue(date);
						$('[name=selected_date_combo]').val(date);
                        $.getScript('schedule.js');
					}
				},
				error:function() {
					alert('Ajax error.');
				}
			})
		})
		$('[name=selected_date_next]').click(function(){
			$.ajax({
				type: 'GET',
				url: 'calendar_table.php',
				dataType: 'html',
				data: {selected_date: $(this).val(),
					start_week_day : $('[name=start_week_day]').val()},
				success: function(data){
					if($('[name=selected_date_next]').val() != ''){
						$('#calendar').find('tr:gt(0)').remove();
						$('#calendar').append(data);
						var date = $('[name=selected_date_next]').val();
						changeMoveButtonValue(date);
						$('[name=selected_date_combo]').val(date);
						$.getScript('schedule.js');
					}
				},
				error:function() {
					alert('Ajax error.');
				}
			})
		})
		$('[name=selected_date_combo]').change(function(){
			$.ajax({
				type: 'GET',
				url: 'calendar_table.php',
				dataType: 'html',
				data: {selected_date : $('[name=selected_date_combo]').val(),
					start_week_day : $('[name=start_week_day]').val()},
				success: function(data){
					$('#calendar').find('tr:gt(0)').remove();
					$('#calendar').append(data);
					var date = $('[name=selected_date_combo]').val();
					changeMoveButtonValue(date);
					$.getScript('schedule.js');
				},
				error:function() {
					alert('Ajax error.');
				}
			})
		});
		$('[name=start_week_day]').change(function(){
			$.ajax({
				type: 'GET',
				url: 'calendar_table.php',
				dataType: 'html',
				data: {selected_date : $('[name=selected_date_combo]').val(),
					start_week_day : $('[name=start_week_day]').val()},
				success: function(data){
					$('#calendar').find('tr:gt(0)').remove();
					$('#calendar').append(data);
					$.getScript('schedule.js');
				},
				error:function() {
					alert('Ajax error.');
				}
			})
		});
		$('#schedule_form_finish_div_close').click(function(){
			$('#schedule_form_finish_div').css('visibility', 'hidden');
			$.ajax({
				type: 'GET',
				url: 'calendar_table.php',
				dataType: 'html',
				data: {selected_date : $('[name=selected_date_combo]').val(),
					start_week_day : $('[name=start_week_day]').val()},
				success: function(data){
					$('#calendar').find('tr:gt(0)').remove();
					$('#calendar').append(data);
					$.getScript('schedule.js');
				},
				error:function() {
					alert('Ajax error.');
				}
			});
		});
	});
		
	function initializeMoveButton(){
		var date = new Date();
		next_month = new Date();
		before_month = new Date();
		next_month.setMonth(date.getMonth() + Number(1));
		before_month.setMonth(date.getMonth() - Number(1));
		
		$('[name=selected_date_before]').val(before_month.getFullYear() + '-' + (before_month.getMonth() + 1));
		$('[name=selected_date_next]').val(next_month.getFullYear() + '-' + (next_month.getMonth() + 1));
		
	}
	function changeMoveButtonValue(date){
		var date_array = date.split('-');
		var date_obj = new Date(date_array[0], date_array[1], 1);
		var over_limit = new Date();
		over_limit.setMonth(over_limit.getMonth() + Number(10));
		var under_limit = new Date();
		under_limit.setMonth(under_limit.getMonth() - Number(11));
		date_obj.setMonth(date_obj.getMonth());
		date_obj.setMonth(date_obj.getMonth() - Number(2));
		if(date_obj < under_limit){
			$('[name=selected_date_before]').val('');
		}else{
			$('[name=selected_date_before]').val(date_obj.getFullYear() + '-' + (date_obj.getMonth() + 1));
		}
		date_obj.setMonth(date_obj.getMonth() + Number(2));
		if(date_obj > over_limit){
			$('[name=selected_date_next]').val('');
		}else{
			$('[name=selected_date_next]').val(date_obj.getFullYear() + '-' + (date_obj.getMonth() + 1));
		}
	}
	
	function appendComboBox(){
		var date = new Date();
		for(var i = -10; i <= 10; ++i){
			var append_date = new Date();
			append_date.setMonth(date.getMonth() + Number(i));
			$('[name=selected_date_combo]').append($('<option>').html(append_date.getFullYear() + '年' + (append_date.getMonth() + 1) + '月')
					.val(append_date.getFullYear() + '-' + (append_date.getMonth() + 1)))
		}
		$('[name=selected_date_combo]').val(date.getFullYear() + '-' + (date.getMonth() + 1));
	}
	
})();

