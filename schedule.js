(function(){
		$(function(){
			
		$('.schedule_registration').click(function(){
			$('#schedule_form_div, #schedule_form_div *:not(#delete, #modify)').css('visibility', 'visible');
			$('#modify, #delete').css('visibility', 'hidden');
			var date = $(this).attr('id');
			initializeSelectBox(date);
			
		});
		
		$('.schedule_link').click(function(){
			$('#schedule_form_div, #schedule_form_div *:not(#register)').css('visibility', 'visible');
			view_id = $(this).attr('id');
			$.ajax({
				url: 'schedule_edit.php',
				type: 'post',
				dataType: 'json',
				data: {view_id : $(this).attr('id'),
					mode: 'view'},
				success: function(data){
					start_time_obj = data['schedule']['start_time'];
					end_time_obj = data['schedule']['end_time'];
					start_date = [start_time_obj['year'], start_time_obj['month'], start_time_obj['day']].join('-');
					end_date = [end_time_obj['year'], end_time_obj['month'], end_time_obj['day']].join('-');
					appendDateSelectBox('[name=schedule_start', start_date);
					appendHourSelectBox('[name=schedule_start', start_time_obj['hour']);
					appendMinuteSelectBox('[name=schedule_start', start_time_obj['minute']);
					appendDateSelectBox('[name=schedule_end', end_date);
					appendHourSelectBox('[name=schedule_end', end_time_obj['hour']);
					appendMinuteSelectBox('[name=schedule_end', end_time_obj['minute']);
					$('#view_id').val(view_id);
					$('#schedule_title').val(data['schedule']['title']);
					$('#schedule_detail').val(data['schedule']['detail']);
					$('#register').css('visibility', 'hidden');
					$('#schedule_form_div, #modify, #delete').css('visibility', 'visible');
				},
				error: function(xhr, textStatus, error){
					alert('Ajax Error.'+param);
					$('#register').attr('disabled', false);
				}
			})
		});
		
		
		$('[name=schedule_start_year]').change(function(){
			changeSelectBox('start');
		});
		$('[name=schedule_start_month]').change(function(){
			changeSelectBox('start');
		});
		$('[name=schedule_end_year]').change(function(){
			changeSelectBox('end');
		});
		$('[name=schedule_end_month]').change(function(){
			changeSelectBox('end');
		});
		

				$('#schedule_form_close').click(function(){
			$('#schedule_form_div, #schedule_form_div *').css('visibility', 'hidden');
			$('#schedule_title, #schedule_detail').val('');
			$('#register').attr('disabled', false);
			$('#error_message').text('');
		});
	});

	function initializeSelectBox(date){
		appendDateSelectBox('[name=schedule_start', date);
		appendDateSelectBox('[name=schedule_end', date);
		
		appendTimeSelectBox('[name=schedule_start');
		appendTimeSelectBox('[name=schedule_end');
	};

	function changeSelectBox(name){
		name = '[name=schedule_' + name;
		date_array = new Array($(name+'_year]').val(), $(name+'_month]').val(), $(name+'_day]').val());
		appendDaySelectBox(name, date_array);
	}

	function appendDateSelectBox(name, date){
		date_array = date.split('-');
		appendYearSelectBox(name, date_array[0]);
		appendMonthSelectBox(name, date_array[1]);
		appendDaySelectBox(name, date_array);
	};
	
	function appendYearSelectBox(name, year){
		name = name + '_year]';
		$(name).children().remove();
		for(var i = 2015; i < 2019; ++i){
			$(name).append($('<option>').html(i).val(i));
		}
        $(name).val(year);
	};
	
	function appendMonthSelectBox(name, month){
	    name = name + '_month]';
		$(name).children().remove();
		for(var i = 1; i <= 12; ++i){
			$(name).append($('<option>').html(i).val(i));
		}
        $(name).val(month);
	};
	
	function appendDaySelectBox(name, date_array){
		name = name + '_day]';
		$before_selected = $(name).val();
		$(name).children().remove();
		var date = new Date(date_array[0], date_array[1], 0);
		var last_day = date.getDate();
		for(var i = 1; i <= last_day; ++i){
			$(name).append($('<option>').html(i).val(i));
		}
		
		if($before_selected > last_day){
			$(name).val(last_day);
		}else{
			$(name).val(date_array[2]);
		}
	};
	
	function appendTimeSelectBox(name){
		var date = new Date();
		appendHourSelectBox(name, date.getHours());
		appendMinuteSelectBox(name, date.getMinutes());
	}
	
	function appendHourSelectBox(name, hour){
		name = name + '_hour]';
		$(name).children().remove();
		for(var i = 0; i <= 23; ++i){
			$(name).append($('<option>').html(i).val(i));
		}
		$(name).val(hour);
	};
	
	function appendMinuteSelectBox(name, minute){
		name = name + '_minute]';
		$(name).children().remove();
		for(var i = 0; i <= 59; ++i){
			$(name).append($('<option>').html(i).val(i));
		}
		$(name).val(minute);
	};
})();