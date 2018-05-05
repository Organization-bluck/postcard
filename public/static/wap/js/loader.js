// JavaScript Document
var imgArray = [
	"images/ask.png",
	"images/arrow.png",
	"images/bg.jpg",
	"images/bg1.jpg",
	"images/dot.png",
	"images/icon.png",
	"images/sound.png",
	"images/sound_muted.png",
	"images/t1.png",
	"images/t2.png",
	"images/text1.png",
	"images/tws.png",
	"images/tws-panel.png",
	"images/txt1.png",
	"images/txt2.png",
	"images/txt3.png",
	"images/txt4.png",
	"images/txt5.png",
	"images/user.png",
	"images/user-tws.png",
	"images/user.png",
	"images/2016.png",
	"images/bldsy1.png",
	"images/btn-submit.png",
	"images/contact.png",
	"images/djg.png",
	"images/djg-panel.png",
	"images/dq1.png",
	"images/dq-panel.png",
	"images/horse.png",
	"images/job.png",
	"images/logo.png",
	"images/name.png",
	"images/school.png",
	"images/txt1.png",
	"images/txt2.png",
	"images/txt3.png",
	"images/txt4.png",
	"images/txt5.png",
	"images/word1.png",
	"images/word2.png",
	"images/word3.png",
	"images/word4.png",
	"images/yml1.png",
	"images/yml-panel.png",
	"images/yzc1.png"
	// "images/sound/deng1.mp3",
	// "images/sound/deng2.mp3",
	// "images/sound/deng3.mp3",
	// "images/sound/ding1.mp3",
	// "images/sound/ding2.mp3",
	// "images/sound/ding3.mp3",
	// "images/sound/tian1.mp3",
	// "images/sound/tian2.mp3",
	// "images/sound/tian3.mp3"


];

// 资源加载
var Loader = function(){
	this.currProgress = 0;  // 当前加载进度
	this.isCompleted = false; // 判断是否加载完毕
	this.total = 100;  // 最大值100

	var loaded = 1;	
	// var content = document.getElementById('content');
	var number = document.getElementById('number');
	// var w = document.getElementsByClassName('progress')[0].offsetWidth / 20;
	this.Loading = function(imgArray){
		var self = this;
		for( var i = 1 ; i < imgArray.length; i++ ){
			var ext = imgArray[i].substring(imgArray[i].lastIndexOf(".")).toLowerCase();
			if( ext == '.png' || ext == '.jpg' || ext == '.jpeg' || ext == '.gif' ){
				var img = new Image();
				img.onload = function(){
					loaded ++;
					self.currProgress = loaded / imgArray.length * 100;
					// content.style.width = self.currProgress / 100 * w+"rem";
					number.innerHTML = (self.currProgress).toFixed(1)+"%";
					console.log(number.innerHTML);
					if( loaded == imgArray.length ){
						$('.loading').animate({opacity:0},1000,function(){
							$(this).remove();
						});
						// $('.loading').addClass('hidden');
						// $('.loading').remove();
					}
				};
				img.onerror = function(){
					loaded ++;
					if( loaded == imgArray.length ){
						$('.loading').animate({opacity:0},1000,function(){
							$(this).remove();
						});
					}
				};
				img.src = imgArray[i];
			}else{
				this.loadMusic(imgArray[i]);
			}
		}
	};
	// this.loadMusic = function(path){
	// 	$.ajax({
	// 		type: 'get',
	// 		url: path,
	// 		data: {},
	// 		async:false,   // false 同步  true  异步
	// 		success: function (data) {
	// 			loaded++;
	// 			if( loaded == imgArray.length ){
	// 				success();  // 回调函数
	// 			}
	// 			//console.log("success");
	// 		},
	// 		error: function (xhr, type)  {
	// 			loaded++;
	// 			if( loaded == imgArray.length ){
	// 				success();  // 回调函数
	// 			}
	// 			//console.log('error');
	// 		}
	// 	})
	// };
	this.success = function(){
		console.log("加载完毕");
		$('.loading').addClass('hidden');
		$('.loading').remove();
	};
	this.loadLoading = function(imgArray,obj){
		var img = new Image();
		img.onload = function(){
			obj.Loading(imgArray,obj.success);
		};
		img.onerror = function(){
			obj.Loading(imgArray,obj.success);
		};
		img.src = imgArray[0];
	};
};
//window.onload = function(){
	var loader = new Loader();
	loader.Loading(imgArray);
//};