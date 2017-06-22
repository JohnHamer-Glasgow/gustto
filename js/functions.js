$(document).ready(function () {
    $('#ttLikeButton').click(function (e) {
	var likeBtn = $(this);
	var like = likeBtn.attr('value');
	var formData = {
	    'csrf_token' : $('input[name="csrf_token"]').val(),
	    'ttID' : likeBtn.data('ttid'),
	};

	if (like == 'not-liked')
	    formData.like = '1';
	else
	    formData.like = '0';

	$.ajax({
	    type        : 'POST', 
            url         : 'ajax/like_tt.php', 
            data        : formData,
            dataType    : 'json',
	}).done(function(data) {
	    if (data.action == 'tt-liked') {
		likeBtn.addClass('tt-liked');
		likeBtn.html("<span class='glyphicon glyphicon-thumbs-up'></span> You like this");
		likeBtn.attr('value', 'liked');
		var numberLikes = $('.tt-likes-number');
		numberLikes.text(parseInt(numberLikes.text()) + 1);
	    } else if (data.action == 'tt-unliked') {
		likeBtn.removeClass('tt-liked');
		likeBtn.html("<span class='glyphicon glyphicon-thumbs-up'></span> Like");
		likeBtn.attr('value', 'not-liked');
		var numberLikes = $('.tt-likes-number');
		numberLikes.text(parseInt(numberLikes.text()) - 1);
	    }
	});
	e.preventDefault();
    });

    $('.deleteFormTT').submit(function (e) {
	var $this = $(this);
	var ttId = $this.find('input[name="ttId"]').val();
	$.ajax({
            type        : 'POST', 
            url         : 'ajax/deleteTT.php', 
            data        : {
		'csrf_token': $this.find('input[name="csrf_token"]').val(),
		'ttId': ttId
	    },
            dataType    : 'json',       
        }).done(function(data) {
            var ttInfo = $('.my-tt-info[value="' + ttId + '"]').parent();
            $('.my-tt-info[value="' + ttId + '"]').remove();
            ttInfo.html("<div class='col-xs-12 alert alert-danger'>Teaching Tip " + data + ".</div>");
	});
	e.preventDefault();
	$('.modal-backdrop').hide(); //for hiding the modal after deleting
    });

    $('.deleteTTView').submit(function (e) {		
	var formData = {
	    'csrf_token': $('input[name="csrf_token"]').val(),
            'ttId': $(this).find('input[name="ttId"]').val()
	};

	$.ajax({
            type        : 'POST', 
            url         : 'ajax/deleteTT.php', 
            data        : formData,
            dataType    : 'json',       
        }).done(function() {
	    $('.teaching-tip').html("<div class='row'><div class='col-xs-12 alert alert-danger'>Teaching Tip deleted.</div></div>");
	});
	e.preventDefault();
    });

    $('.tt-comment-form').submit(function (e) {
    	var formData = {
            'csrf_token': $('input[name="csrf_token"]').val(),
            'comment': $('textarea[name="inputComment"]').val(),
            'ttID': $('input[name="ttID"]').val()
        };
        $.ajax({
            type        : 'POST', 
            url         : 'ajax/tt_add_comment.php', 
            data        : formData,
            dataType    : 'json'
        }).done(function(data) {
            var newComment = "<div class='tt-comment'>";
            newComment += "<div class='tt-comment-profile'>";
            newComment += "<div class='tt-comment-profile-img col-md-1 col-sm-1 col-xs-1'>";
            newComment += "<img class='img responsive img-circle' src='"+ data.a_profilepic +"' alt='profile picture'>";
            newComment += "</div>";
            newComment += "<div class='tt-comment-profile-details col-md-2 col-sm-11 col-xs-11'>";
            newComment += "<div class='tt-comment-profile-name'>" + data.a_firstname + " " + data.a_lastname + "</div>";
            newComment += "<div class='tt-comment-datetime'>" + data.c_time + "</div>";
            newComment += "<div class='tt-comment-options'><a class='tt-edit-comment-btn' role='button' data-target="+ data.c_id +">Edit</a> . <form class='tt-comment-delete-form' action='ajax/tt_delete_comment.php' method='post'><a role='button' class='tt-comment-delete-btn' type='submit' data-cid='" + data.c_id + "'>Delete</a></form></div>";
            newComment += "</div>";
            newComment += "<div class='tt-comment-body tt-comment-body-"+ data.c_id +" col-md-9 col-sm-12 col-xs-12'>";
            newComment += "<div class='arrow_box hidden-sm hidden-xs'></div>";
            newComment += "<div class='arrow_box-up visible-sm visible-xs'></div>";
            newComment += "<div class='tt-comment-body-text'>" + data.comment + "</div>";
            newComment += "<form class='tt-comment-edit-form' action='ajax/tt_edit_comment' method='post'>";
            newComment += "<textarea class='form-control tt-comment-edit-box' name='edit_comment_"+ data.c_id +"' required>"+ data.comment +"</textarea>";
            newComment += "<button type='submit' class='btn btn-default btn-edit-comment-submit' data-cid="+ data.c_id +">Save Changes</button>";
            newComment += "<button type='button' class='btn btn-default btn-edit-comment-cancel'>Cancel</button>";
            newComment += "</form>";
            newComment += "</div>";
            newComment += "</div>";
            newComment += "</div>";
            $('.tt-comments').append(newComment);
            $('textarea[name="inputComment"]').val('');
        });
        e.preventDefault();
    });

    var KEYWORD_MIN_LENGTH = 2;
    $('#inputTTKeywords').keyup(function() {
	var keywords_string = $("#inputTTKeywords").val();
	var keywords = keywords_string.split(/,\s*/);
	var last_keyword = keywords[keywords.length - 1];
	if (last_keyword.length >= KEYWORD_MIN_LENGTH) {
	    $.get( "ajax/tt_keyword_autocomplete.php", { keyword: last_keyword }, null, 'json')
		.done(function( data ) {
		    $('#tt-keyword-results').show();
		    $('#tt-keyword-results ul').html('');
		    $(data).each(function(index, kw) {
			var kws_matching = '<li class="tt-keyword-result">'+ kw.keyword +'</li>';
			$('#tt-keyword-results ul').append(kws_matching);
			$('.tt-keyword-result').unbind().click(function () {
			    var new_kw = $(this).text();
			    var current_keywords_string = '';
			    if (keywords.length > 1) {
				keywords.splice(-1,1)
				current_keywords_string = keywords.join(', ') + ', ';
			    }
                            $("#inputTTKeywords").val(current_keywords_string + new_kw + ', ');
			    $("#tt-keyword-results").fadeOut(500);
			    $('#inputTTKeywords').focus();
			});
		    })
		});
	} else {
            $('#tt-keyword-results ul').html('');
            $('#tt-keyword-results').hide();
	}
    });
    
    $("#inputTTKeywords").blur(function(){
	$("#tt-keyword-results").fadeOut(500);
    }).focus(function() {
        var keyword = $('#inputTTKeywords').val();
        if (keyword.length>KEYWORD_MIN_LENGTH)
            $("#tt-keyword-results").show();
    });

    // autocomplete for sharing a teaching tip
    var MIN_LENGTH = 2;
    $("#tt-share-recipient").keyup(function() {
	var keyword = $("#tt-share-recipient").val();
	if (keyword.length >= MIN_LENGTH) {
	    $.get( "ajax/tt_share_auto_complete.php", { keyword: keyword }, null, 'json')
		.done(function( data ) {
		    $('#tt-share-recipient-results').show();
		    $('#tt-share-recipient-results ul').html('');
		    $(data).each(function(index, user) {
			var userMatching = "<li class='tt-share-recipient-result'>";
			userMatching += "<img class='tt-share-recipient-img img-responsive img-circle' src='"+ user.profile_picture +"' alt='profile picture'>";
			userMatching += "<div class='tt-share-recipient-info'>";
			userMatching += "<div class='tt-share-recipient-name'><a role='button'>" + user.name + " " + user.lastname + "</a></div>";
			userMatching += "<div class='tt-share-recipient-email'>" + user.email + "</div>";
			userMatching += "</div>";
			userMatching += "</li>";
			$('#tt-share-recipient-results ul').append(userMatching);
			$('.tt-share-recipient-result').click(function () {
			    var recipientEmail = $(this).find('.tt-share-recipient-email').html();
			    $("#tt-share-recipient").val(recipientEmail);
			});
		    })
		});
	} else {
	    $('#tt-share-recipient-results ul').html('');
	    $('#tt-share-recipient-results').hide();
	}
    });
    
    // when user clicks somewhere else
    $("#tt-share-recipient").blur(function(){
	$("#tt-share-recipient-results").fadeOut(500);
    }).focus(function() {
	var keyword = $('#tt-share-recipient').val();
	if (keyword.length>MIN_LENGTH)
	    $("#tt-share-recipient-results").show();
    });
    
    // autocomplete for contributors on Add Teaching Tip
    $(document).on('keyup', ".add-contributor-form-input", function() {
	var keyword = $(this).val();
	var contributorInput = $(this);
	var resultList = $(this).siblings('.fg-contributor-results');
	if (keyword.length >= MIN_LENGTH) {
	    $.get( "ajax/tt_share_auto_complete.php", { keyword: keyword }, null, 'json')
		.done(function( data ) {
		    resultList.show();
		    resultList.find('ul').html('');
		    $(data).each(function(index, user) {
			var userMatching = "<li class='fg-contributor-result'>";
			userMatching += "<img class='tt-share-recipient-img img-responsive img-circle' src='"+ user.profile_picture +"' alt='profile picture'>";
			userMatching += "<div class='tt-share-recipient-info'>";
			userMatching += "<div class='tt-share-recipient-name'><a role='button'>" + user.name + " " + user.lastname + "</a></div>";
			userMatching += "<div class='tt-share-recipient-email'>" + user.email + "</div>";
			userMatching += "</div>";
			userMatching += "</li>";
			resultList.find('ul').append(userMatching);
			$('.fg-contributor-result').click(function () {
			    var contributorEmail = $(this).find('.tt-share-recipient-email').html();
			    contributorInput.val(contributorEmail);
			});
		    })
		});
	} else {
            resultList.find('ul').html('');
            resultList.hide();
	}
    });
    

    // when user clicks somewhere else
    $(document).on("blur", ".add-contributor-form-input", function(){
	$(this).siblings(".fg-contributor-results").fadeOut(500);
    });
    $(document).on("focus", ".add-contributor-form-input", function () {
	$(this).siblings(".fg-contributor-results").show();
    });
    
    $('#tt-share-send').click(function (e) {
   	var message = $.trim($('#tt-share-message').val()); 
	var formData = {
            'csrf_token': $('input[name="csrf_token"]').val(),
            'ttID': $('input[name="ttID"').val(),
            'recipient': $('input[name="recipient"]').val(),
        };
   	if (message != '') formData.message = message;
        $.ajax({
            type        : 'POST', 
            url         : 'ajax/tt_share.php', 
            data        : formData,
            dataType    : 'json',       
        }).done(function (data) {
            if (data.success == '1') {
		$("#tt-share-recipient").val('');
		$('.tt-share-alert').remove();
		$('#shareModal').modal('hide');
            } else if(data.error) {
		$('.tt-share-alert').remove();
                var alert = '<div class="tt-share-alert alert alert-danger alert-dismissible" role="alert">';
		alert += '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
		alert += '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>';
		alert += '<strong>'+ data.error +'</strong></div>';
            }
	    $('.tt-share-error').append(alert);
        }).fail (function (data) {
            console.log(data);
        });       
        e.preventDefault();
    });
    
    $('#filterAll').click(function(){
   	//Tick icon
 	$('.ok-all').css("display","block");
   	$('.ok-liked').css("display","none");
   	$('.ok-commented').css("display","none");
   	$('.ok-shared').css("display","none");
   	//Message
   	$('.no-activity').css("display","block");
   	$('.no-like-activity').css("display","none");
   	$('.no-comment-activity').css("display","none");
   	$('.no-share-activity').css("display","none");
   	//Activiy
   	$('.liked-row-wrapper').show();
   	$('.commented-row-wrapper').show();
   	$('.shared-row-wrapper').show()
	$('.activity-dropdown-text').html('All  ');
    });

    $('#filterLikes').click(function(){
   	$('.ok-all').css("display","none");
   	$('.ok-liked').css("display","block");
   	$('.ok-commented').css("display","none");
   	$('.ok-shared').css("display","none");
   	//Message
   	$('.no-activity').css("display","none");
   	$('.no-like-activity').css("display","block");
   	$('.no-comment-activity').css("display","none");
   	$('.no-share-activity').css("display","none");
	$('.liked-row-wrapper').show();
   	$('.commented-row-wrapper').hide();
   	$('.shared-row-wrapper').hide()
	$('.activity-dropdown-text').html('My Likes  ');
    });

    $('#filterComments').click(function(){
   	$('.ok-all').css("display","none");
   	$('.ok-liked').css("display","none");
   	$('.ok-commented').css("display","block");
   	$('.ok-shared').css("display","none");
   	//Message
   	$('.no-activity').css("display","none");
   	$('.no-like-activity').css("display","none");
   	$('.no-comment-activity').css("display","block");
   	$('.no-share-activity').css("display","none");
   	$('.liked-row-wrapper').hide();
   	$('.commented-row-wrapper').show();
   	$('.shared-row-wrapper').hide()
   	$('.activity-dropdown-text').html('My Comments  ');
    });

    $('#filterShares').click(function(){
   	$('.ok-all').css("display","none");
   	$('.ok-liked').css("display","none");
   	$('.ok-commented').css("display","none");
   	$('.ok-shared').css("display","block");
   	//Message
   	$('.no-activity').css("display","none");
   	$('.no-like-activity').css("display","none");
   	$('.no-comment-activity').css("display","none");
   	$('.no-share-activity').css("display","block");
	$('.liked-row-wrapper').hide();
   	$('.commented-row-wrapper').hide();
   	$('.shared-row-wrapper').show()
	$('.activity-dropdown-text').html('My Shares  ');
    });

    // delete teaching tip comment ajax
    $(document).on('click', '.tt-comment-delete-btn', function (e) {
   	var deleteBtn = $(this);
	var formData = {
  	    'csrf_token' : $('input[name="csrf_token"]').val(),
  	    'cID' : $(this).data('cid'),
  	};
	$.ajax({
  	    type        : 'POST', 
            url         : 'ajax/tt_delete_comment.php', 
            data        : formData,
            dataType    : 'json',
  	}).done(function( data ) {
  	    if (data.action == 'deleted') {
  		deleteBtn.parent().parent().parent().parent().parent().remove();
  	    }
  	});
  	e.preventDefault();
    });
    
    // Edit teaching tip comment ajax
    $(document).on('click', '.tt-edit-comment-btn', function () {
	var commentID = $(this).data('target');
	var comment = $('.tt-comment-body-' + commentID);
	var commentBody = comment.find('.tt-comment-body-text');
	var commentEditForm = comment.find('.tt-comment-edit-form');
	commentBody.hide();
	commentEditForm.show();
    });

    $(document).on('click', '.btn-edit-comment-cancel', function () {
	var comment = $(this).parent().parent();
	var commentBody = comment.find('.tt-comment-body-text');
	var commentEditForm = comment.find('.tt-comment-edit-form');
	commentBody.show();
	commentEditForm.hide();
    });

    $(document).on('click', '.btn-edit-comment-submit', function (e) {
	var commentID = $(this).data('cid');
	var comment = $('.tt-comment-body-' + commentID);
	var commentBody = comment.find('.tt-comment-body-text');
	var commentEditForm = comment.find('.tt-comment-edit-form');
	var formData = {
            'csrf_token' : $('input[name="csrf_token"]').val(),
            'cID' : commentID,
            'comment': $('textarea[name="edit_comment_'+ commentID +'"]').val(),
        };
	$.ajax({
            type        : 'POST', 
            url         : 'ajax/tt_edit_comment.php', 
            data        : formData,
            dataType    : 'json',
	}).done(function( data ) {
            if (data.action == 'edited') {
		commentBody.text(data.comment);
		commentBody.show();
		commentEditForm.hide();
            }            
        });
	e.preventDefault();
    });
    
    //Quick Search Autocomplete  -- GET
    var keyword_min = 2;
    $('#tt-search-input').keyup(function(){
	var keyword = $('#tt-search-input').val();
	if (keyword.length>keyword_min){
            $.get("ajax/qsearch.php",{keyword:keyword}, null, 'json')
		.done(function(data) {
		    $('#tt-qsearch-results').show();
		    $('#tts_result').html('');
		    $('#users_result').html('');
		    if (data['tts'].length == 0 && data['users'].length == 0 && data['keywords'].length == 0)
			$('#tt-qsearch-results').hide();
		    else {
			$(data['tts']).each(function(index, tt) {
			    $('#tts_result')
				.append("<li><a class='tt-qsearch-title' href='teaching_tip.php?ttID="+tt.id+"'><span class='glyphicon glyphicon-file'></span>"+" "+ tt.title + "</a></li>");
			});
			$(data['keywords']).each(function(index, tt) {
			    $('#tts_result')
				.append("<li><a class='tt-qsearch-title' href='teaching_tip.php?ttID="+tt.id+"'><span class='glyphicon glyphicon-file'></span>"+" "+ tt.title + "</a></li>");
			});
			$(data['users']).each(function(index, user) {
			    $('#users_result')
				.append("<li><a class='tt-qsearch-title' href='profile.php?usrID="+user.id+"'><span class='glyphicon glyphicon-user'></span>"+" "+user.name +" "+user.lastname+"</a></li>");
			});
			$('#tt-qsearch-results').show();
		    }
		});
	} else {
            $('#tt-qsearch-results').hide();
	}
    });
    
    // when user clicks somewhere else
    $("#tt-search-input").blur(function(){
	$("#tt-qsearch-results").fadeOut(500);
    }).focus(function() {
        var keyword = $('#tt-search-input').val();
        if (keyword.length>keyword_min)
            $("#tt-qsearch-results").show();
    });
    
    //View more teaching tips -- User Profile
    $('.profile-view-more').click(function(){
	$(this).hide();
	$('.profile-view-more-tts-wrapper').show();
	var userId = $(this).data('user-id');
	$.get( "ajax/view_more_profile.php", { userId: userId }, null, 'json')
            .done(function(data) {
		$(data).each(function(i,tt){
		    var temp = "<div class='profile-view-more-tts-tt'>";
                    temp += "<a href='teaching_tip.php?ttID="+tt.id+"'>"+tt.title+"</a>";
                    temp += "<p>"+tt.description+"</p></div>";
		    $('.profile-view-more-tts-wrapper').append(temp);
		})
            })
    });

    $('.homepage-view-more-contrib').click(function() {
	var offset = $(this).attr('value');
        $.get("ajax/view-more-contrib.php", {offset: offset}, null, 'json')
            .done(function(data) {
		if (data.length == 0) {
		    var temp = "<div class='feed-tt'>" 
		    temp += "<div class='row col-xs-12'>";
		    temp += "<strong>There are no more Teaching Tip contributors to display.</strong>";
		    temp += "</div>";
		    $('.feed-tts').append(temp);
		    $('.homepage-view-more-contrib').hide();
		} else
		    $(data).each(function(i, d) {
			var temp = "<div class='feed-tt'>" 
			temp += "<div class='row'>";
			temp += "<div class='col-sm-2 feed-profile hidden-xs'>";
			temp += "<img class='img-circle' src='" + d.user.profile_picture + "' alt='profile picture'>";
			temp += "</div>";
			temp += "<div class='col-xs-12 col-sm-10 feed-info'>";
			temp += "<div class='row visible-xs'>";
			temp += "<div class='col-xs-2 feed-profile'>";
			temp += "<img class='img-circle img-responsive' src='" + d.user.profile_picture + "' alt='profile picture'>";
			temp += "</div>";
			temp += "<div class='col-xs-10'>";
			temp += "<h4 class='feed-tt-title'><a href='profile.php?usrID=" + d.user.id + "'>" + d.user.name + " " + d.user.lastname + "</a></h4>";
			temp += "<span class='feed-tt-profile-school'>" + d.user.school + "</span>";
			temp += "</div>";
			temp += "</div>";
			temp += "<div class='feed-title hidden-xs'>";
			temp += "<h4 class='feed-tt-title'><a href='profile.php?usrID=" + d.user.id + "'>" + d.user.name + " " + d.user.lastname + "</a></h4>";
			temp += "</div>";
			temp += "<div class='feed-text-wrapper'>";
			temp += "<strong>" + d.n + " " + (d.n == 1 ? "Teaching Tip" : "Teaching Tips") + "</strong>";
			temp += "</div>";
			temp += "</div>";
			temp += "</div>";
			$('.feed-tts').append(temp);
		    });
	    });
	offset = parseInt(offset) + 10;
	$(this).attr('value', offset.toString());
    });
    
    //View more home teaching tips  -- Home Page
    $('.homepage-view-more').click(function() {
	var filterType = $(this).data('filtertype');
	var period = $(this).data('period');
	var offset = $(this).attr('value');
	var finished = function () {
	    var temp = "<div class='feed-tt'>" 
	    temp+="<div class='row col-xs-12'>";
	    temp+="<strong>There are no more Teaching Tips to display.</strong>";
	    temp+="</div></div>";
	    $('.feed-tts').append(temp);
	    $('.homepage-view-more').hide();
        };
        $.get("ajax/view_more_home.php", {filterType: filterType, period: period, offset: offset}, null, 'json')
            .done(function(data) {
		if (data.length == 0)
		    finished();
		$(data).each(function(i, tt) {
		    var temp = "<div class='feed-tt'>" 
		    temp+="<div class='row'>";
		    temp+="<div class='col-sm-2 feed-profile hidden-xs'>";
		    temp+="<img class='img-circle' src='"+ tt.author.profile_picture +"' alt='profile picture'>";
		    temp+="<a href='profile.php?usrID="+tt.author.id+"' class='col-xs-12 tt-profile-name '>"+tt.author.name+" "+tt.author.lastname+"</a>";
		    temp+="<div class='feed-tt-profile-school'>" + tt.author.school + "</div>";
		    temp+="<div class='clearfix'></div>";
		    temp+="<span class='feed-tt-time'>"+tt.tt_time+"</span>";
		    temp+="</div>";
		    temp+="<div class='col-xs-12 col-sm-10 feed-info'>";
		    temp+="<div class='row visible-xs'>";
		    temp+="<div class='col-xs-2 feed-profile'>";
		    temp+="<img class='img-circle img-responsive' src='"+ tt.author.profile_picture +"' alt='profile picture'>";
		    temp+="</div>";
		    temp+="<div class='col-xs-10'>";
		    temp+="<h4 class='feed-tt-title'><a href='teaching_tip.php?ttID="+tt.id+"'>"+tt.title+"</a></h4>";
		    temp+="<span class='feed-tt-time'>"+tt.tt_time+"</span> ";
		    temp+="</div>        </div>";
		    temp+="<div class='feed-title hidden-xs'>";
		    temp+="<h4 class='feed-tt-title'><a href='teaching_tip.php?ttID="+tt.id+"'>"+tt.title+"</a></h4>";
		    temp+="</div>";
		    temp+="<div class='feed-text-wrapper'>";
		    temp+="<p class='feed-text'>"+tt.description+"</p>";
		    temp+="</div>";
		    
		    if (tt.keywords) {
			temp += "<div class='feed-tt-keywords'>";
			tt.keywords.forEach(function (kw) {
			    temp += "<a href='search.php?q="+ kw.keyword +"&o=keyword'><div class='tt-keyword'>"+ kw.keyword +"</div></a>";
			});
			temp += "</div>";
		    }
		    
		    temp+="<div class='feed-icons-wrapper'>";
		    if(tt.user_likes == 1) {
			temp+="<div class='feed-icons'><span class='glyphicon glyphicon-thumbs-up glyphicon-liked'></span> "+tt.number_likes+"</div>";
		    } else {
			temp+="<div class='feed-icons'><span class='glyphicon glyphicon-thumbs-up'></span> "+tt.number_likes+"</div>";
		    }
		    temp+="<div class='feed-icons'><span class='glyphicon glyphicon-comment'></span> "+tt.number_comments+"</div>";
		    temp+="<div class='feed-icons'><span class='glyphicon glyphicon-share-alt'></span> "+tt.number_shares+"</div>";
		    temp+="</div>";
		    temp+=" </div>      </div>      </div>";
		    $('.feed-tts').append(temp);
		})
            }).fail(finished);  
	offset = parseInt(offset) + 10;
	$(this).attr('value', offset.toString());
    });
    
    // View more Notifications
    // changing encode html to characters
    function htmlDecode(value) {
	return $("<textarea/>").html(value).text();
    };
    
    $('.notifications-view-more').click(function() {
	var finished = function () {
	    var temp ="<div class='row notification-wrapper no-more-notifications'>";
	    temp+="<strong>There are no more Notifications to display.</strong>";
	    temp+="</div>";
	    $('.notifications-wrapper').append(temp);
	    $('.notifications-view-more').hide();
	};
	var offset = parseInt($(this).attr('value'));
	$(this).attr('value', offset + 10);
	$.get("ajax/view_more_notifications.php", {offset: offset}, null, 'json')
            .done(function(data) {
		if (data == 'done')
		    finished();
		else
                    $('.notifications-wrapper').append(htmlDecode(data['notifications']));
            })
	    .fail(finished);
    });

    //initialize tooltip
    $('#btn-add-contributor-tooltip').tooltip();
    $('.home-filter-tooltip').tooltip();
    $('.tt-add-form-tooltip').tooltip();
    
    // Add contributors to a TT (TT add page)
    var numberContributors = $('.fg-contributor').length;
    
    $('.btn-add-contributor').click(function () {
	numberContributors += 1;
	var contributorInput = '<div class="form-group fg-contributor" id="fg-contributor-'+ numberContributors +'">';
	contributorInput += '<label for="contributor'+ numberContributors +'" class="add-contributor-form-label">Co-author '+ numberContributors +': </label>';
	contributorInput += '<input type="text" class="form-control add-contributor-form-input" id="contributor'+ numberContributors +'" name="contributors[]" placeholder="Co-author\'s email address" autocomplete="off">';
	contributorInput += '<button type="button" class="glyphicon glyphicon-remove btn-remove-contributor" data-target="'+ numberContributors +'"></button>'
	contributorInput += '<div class="fg-contributor-results"><ul></ul></div>';
	contributorInput += '</div>';
	$('.tt-add-form-contributors').append(contributorInput);
    });
    
    $(document).on('click', '.btn-remove-contributor', function () {
	var target = $(this).data('target');
	$('#fg-contributor-' + target).remove();
	if (numberContributors <= parseInt(target)) numberContributors -= 1;
    });
    
    // Notifications coloring
    function colorChange(){
	var notificationNo = $('.header-notificationNo').html();
	if (notificationNo.trim() == '(0)')
	    $('.notification-button').removeClass('notification-button-yellow');
	else
	    $('.notification-button').addClass('notification-button-yellow');
    }

    colorChange();
  
    $(document).on('click', '.notification-teachingtip', function() {
	var notificationId = $(this).data('id');
	$.get("ajax/notification_seen.php",{notificationId:notificationId}, null, 'json')
    });
    
    $('.notification-update').click(function(){
	var notificationId = $(this).attr('value');
	$.get("ajax/notification_seen.php",{notificationId:notificationId}, null, 'json')
    });
    
    var update = setInterval(function () {
	$.get("ajax/reload_notifications.php",{}, null, 'json')
            .done(function(data){ 
		var notificationNo = data['notificationNo'];
		var notifications = data['notifications'];
		$('.header-notificationNo').html(notificationNo);
		colorChange();
                $('.notification-menu').html(htmlDecode(notifications));
            })
    }, 1000 * 60 * 5);

    $('#allRead').click(function(){
	$.get("ajax/markNotificationsRead.php")
    });
    
    $('#follow-btn').click(function (e) {
	var followBtn = $(this);
	var userID = followBtn.data('uid');
	var followed = followBtn.attr('data-followed');
	var formData = {
	    'csrf_token' : $('input[name="csrf_token"]').val(),
	    'userID'     : userID,
	    'followed'   : followed,
        };
	$.ajax({
	    type        : 'POST', 
	    url         : 'ajax/follow_user.php', 
	    data        : formData,
	    dataType    : 'json',
	}).done(function(data) {
            if (data.followed == 'followed') {
		followBtn.html('<span class="glyphicon glyphicon-signal"></span> Following');
		followBtn.addClass('btn-followed');
		followBtn.attr('data-followed', '1');
            } else if (data.followed == 'unfollowed') {
		followBtn.html('<span class="glyphicon glyphicon-signal"></span> Follow');
		followBtn.removeClass('btn-followed');
		followBtn.removeClass('btn-unfollow');
		followBtn.attr('data-followed', '0');
            }        
	}).fail(function(data){
            console.log(data);
	});
	e.preventDefault();
    });

    $('#follow-btn').hover(
	function () {
	    var followBtn = $(this);
	    var following = followBtn.attr('data-followed') == '1';
	    if (following) {
		followBtn.addClass('btn-unfollow');
		followBtn.html('<span class="glyphicon glyphicon-remove"></span> Unfollow')
	    }
	},
	function () {
	    var followBtn = $(this);
	    var following = followBtn.attr('data-followed') == '1';
	    if (following) {
		followBtn.removeClass('btn-unfollow');
		followBtn.html('<span class="glyphicon glyphicon-signal"></span> Following')
	    }
	});
    
    $('.btn-change-profile-pic').click(function () {
	$('.change-profile-pic-form').submit();
    });
    
    $('.carousel').carousel({
	interval: 8000
    });
    
    $('.carousel').carousel(Math.floor(Math.random() * 5));
});
