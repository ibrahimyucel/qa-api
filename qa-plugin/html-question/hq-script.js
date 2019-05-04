	document.addEventListener("DOMContentLoaded", init, false);
	
	function init() {
		document.querySelector('#questions').addEventListener('change', handleFileSelect, false);
		selDiv = document.querySelector("#selectedFiles");
		countDiv = document.querySelector("#countfiles");
	}
		
	function handleFileSelect(e) {		
		if(!e.target.files) return;
		
		selDiv.innerHTML = "";
		var i=0;
		var files = e.target.files;
		for(i=0; i<files.length; i++) {
			var file = files[i];
			var reader = new FileReader();
			var text = "";
			reader.onload = function (e) { text = e.target.result; };
			//contents = $h.arrayBuffer2String(reader.result);
			reader.readAsText(file, 'UTF-8');
			
			selDiv.innerHTML += "<tr>" +
				"<td>" + (i + 1) + ". " + "</td>" +
				//"<td>" + text + "</td>" +
				"<td>" + file.name + "</td>" +
				"<td>" + formatBytes(file.size) + "</td>" +
				"</tr>";
		}
		countDiv.innerHTML = "<input type=\"hidden\" name=\"countfile\" value=\"" + i + "\"/>";
	}
	
	
	function formatBytes (bytes, decimals) {
		if (bytes === 0) return '0 Bytes';
		var k = 1024;
		var dm = decimals + 1 || 3;
		var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		var i = Math.floor(Math.log(bytes) / Math.log(k));
		return (bytes / Math.pow(k, i)).toPrecision(dm) + ' ' + sizes[i];
	}
	
	function submit_questions(){	
		$.ajax({  
			url: 'http://localhost/qtoa/qa-plugin/html-question/hq-ajax.php',  
			beforeSend: function() 
			{
				$("#selectedFiles").hide();
			},  
			success: function(response)
			{
				//$("#itemsearchreslt").show();
				$("#feedback").html(response);
			}	   
		});
	}
	