(function( $ ) {
	'use strict';

	var enco_overlay = false;
	var enco_explanation_displayed = false;

	$( document ).ready(function() {

		// Remove admin bar's html margin
		$( 'html' ).addClass( 'enco-nomargin' );

		var locHash = window.location.hash;
		if( locHash.match("^#comment-") ) {
			
			var commentId = parseInt( locHash.split('-')[1] );
			var encoCommentId = '#enco-comment-' + commentId;
			var is_orphan = true;

			enco_hide_threads();

			if ( $( encoCommentId ).parents('.enco-overlay').length ) {
				enco_open();
				is_orphan = false;
			}
			
			$( encoCommentId ).closest( '.enco-thread' ).show();
			$( encoCommentId ).addClass( 'enco-comment-green' );

			if( !is_orphan ) {
				$( '.enco-overlay' ).animate({
			        scrollTop: $( encoCommentId ).offset().top
			    }, 2000);
			}

		}

		$('#enco-start-thread').submit( function(e) {

			var data = {
				'action': 		'start_new_thread',
				'body': 		$(this).serialize()
			};

			$.post( 
				ajax_object.ajax_url,
				data,
				function( obj ) { 

					if( obj.status == 1 ) {
						enco_insert_thread( obj.thread_id, obj.output );
					} else if( obj.status == 0 ) {
						enco_tooltip( 'Your thread will be publicly displayed after moderation.', 'info' );
					} else if( obj.status == -1 ) {
						enco_tooltip( 'Your thread has been blocked by our spam checks. Sorry.', 'error' );
					} else if( obj.status == 'too_many' ) {
						enco_tooltip( 'Oops! Too many threads for this article; just join an existing one!', 'error' );
					} else {
						enco_tooltip( 'There has been an error with your thread.', 'error' );
					}
				},
				'json'
			);

			e .preventDefault();
			return false;
		});

		$('.enco-reply-form').submit( function(e) {

			var data = {
				'action': 		'thread_reply',
				'body': 		$(this).serialize()
			};

			$.post( 
				ajax_object.ajax_url,
				data,
				function( obj ) { 

					if( obj.status == 1 ) {
						enco_insert_comment( obj.thread_id, obj.comment_parent, obj.output );
					} else if( obj.status == 0 ) {
						enco_tooltip( 'Your comment will be publicly displayed after moderation.', 'info' );
					} else if( obj.status == -1 ) {
						enco_tooltip( 'Your comment has been blocked by our spam checks. Sorry.', 'error' );
					} else {
						enco_tooltip( 'There has been an error with your comment.', 'error' );
					}
				},
				'json'
			);

			e .preventDefault();
			return false;
		});

		$(document).scroll(function() {
		    if( $('#enco-content').length && $(this).scrollTop() >= $('#enco-content').position().top ){
		    	if( ajax_object.too_many_threads != 1 && !enco_explanation_displayed ) {
		        	enco_explanation_displayed = true;
					enco_tooltip( 'Select a sentence to start a new conversation.', 'info' );
				}
		    }
		});

	    $('.enco-close-btn').on('click', function(e){
	    	enco_close();
	    });

	    $('.enco-content').on('click', function(e){

	    	snapSelectionToWord();
	    	var	text = $.trim( getHTMLOfSelection() );

			if( text.length > 3 ) {
	    		
		    	var occ 		= getOccurrence( text, e );
				var wc  		= wordCount( text );

		    	if( ajax_object.too_many_threads != 1		&&
		    		$('#enco-start-thread').length 			&&
		    		!isTextInClass( text, 'enco-subject' ) 	&& 
		    		wc >= ajax_object.subject_min_words		&& 
		    		wc <= ajax_object.subject_max_words		&&
		    		occ > -1 ) { 
		    		
		    		enco_show_start_thread( text, occ );
				} else if( ajax_object.too_many_threads == 1 ) {
					enco_tooltip( 'Oops! Too many threads for this article; just join an existing one!', 'error' );
				} else if( wc < ajax_object.subject_min_words ) {
					enco_tooltip( 'Select a longer sentence to start a new thread. Minimum ' + ajax_object.subject_min_words + ' word(s).', 'error' );
				} else if( wc > ajax_object.subject_max_words ) {
					enco_tooltip( 'Select a shorter sentence to start a new thread. Maximum ' + ajax_object.subject_max_words + ' word(s).', 'error' );
				} else {

				}

				text = '';
				occ  = -1;
				wc = -1;

			}

	    });

	    $('.enco-content').on('click', '.enco-subject', function(e){
			
			enco_show_thread( $(this).data('id') );
	    });

	    $(document).on('click', '.enco-comment-delete-link', function(e){

			var data = {
				'action': 		'delete_comment',
				'comment_id': 	$(this).data('id')
			};

			$.post( 
				ajax_object.ajax_url,
				data,
				function( obj ) { 

					if( obj.deleted == '1' ) {
						$('#enco-comment-' + obj.comment_id).addClass('enco-comment-deleted');
					} else {
						alert('The comment could not be deleted.');
					}
				},
				'json'
			);
	    });

	    $(document).on('change', '.enco-orphan-admin-move-to', function(e){

			var data = {
				'action': 		'set_thread',
				'comment_id': 	$(this).data('comment-id'),
				'thread_id': 	$(this).val()
			};

			$.post( 
				ajax_object.ajax_url,
				data,
				function( obj ) { 

					if( obj.status == '1' ) {
						$( '#enco-children-of-' + obj.thread_id + '-0' ).append( obj.output );
						$( '#enco-orphan-' + obj.comment_id ).hide();
					} else {
						alert('The comment could not be moved.');
					}
				},
				'json'
			);
	    });

	    $(document).on('click', '.enco-comment-reply-link', function(e){

	    	enco_reply_change_parent( $(this).data('thread-id'), $(this).data('id') );
	    	enco_reply_link_click( $(this).data('thread-id'), $(this).data('id') );
	    });

	    $(document).on('click', '.enco-thread .enco-reply-close', function(e){
	    	
	    	enco_reply_close( $(this).data('thread-id') );
	    });

	    $(document).on('click', '.enco-start-thread .enco-reply-close', function(e){
	    	
	    	enco_close();
	    });

	    $(document).mouseup(function (e) {
	    	
	    	enco_overlay_close(e);
		});

	});

	function enco_tooltip( message, cssclass ) {

		$('#enco-tooltip').addClass( cssclass ).html( message );
		$('#enco-tooltip').fadeIn('slow').delay(5000).fadeOut('slow');
	}

	function enco_insert_thread( thread_id, output ) {

		$( "#enco-overlay" ).prepend( output );
		enco_hide_threads();
		enco_show_thread( thread_id );
	}
	
	function enco_insert_comment( thread, parent, output ) {

		enco_reply_close( thread );
		enco_reply_change_parent( thread, 0 );

		var count_el = $( '#subject-' + thread + ' .enco-comment-count' );
		var old_count = parseInt( count_el.html() );
		count_el.html( old_count+1 );

		$( '#enco-reply-form-' + thread + ' #enco_comment_name, #enco-reply-form-' + thread + ' #enco_comment_email, #enco-reply-form-' + thread + ' #enco_comment_url, #enco-reply-form-' + thread + ' #enco_comment_content' ).val('');
		$( '#enco-children-of-' + thread + '-' + parent ).append( output );
	}

	function isOrContains( node, container ) {
	    while (node) {
	        if (node === container) {
	            return true;
	        }
	        node = node.parentNode;
	    }
	    return false;
	}

	function elementContainsSelection( el ) {
	   
	    var sel;
	    if (window.getSelection) {
	        sel = window.getSelection();
	        if (sel.rangeCount > 0) {
	            for (var i = 0; i < sel.rangeCount; ++i) {
	                if (!isOrContains(sel.getRangeAt(i).commonAncestorContainer, el)) {
	                    return false;
	                }
	            }
	            return true;
	        }
	    } else if ( (sel = document.selection) && sel.type != "Control") {
	        return isOrContains(sel.createRange().parentElement(), el);
	    }
	    return false;
	}

	function snapSelectionToWord() {
	    
	    var sel;

	    // Check for existence of window.getSelection() and that it has a
	    // modify() method. IE 9 has both selection APIs but no modify() method.
	    if (window.getSelection && (sel = window.getSelection()).modify) {
	        sel = window.getSelection();
	        if (!sel.isCollapsed) {

	            // Detect if selection is backwards
	            var range = document.createRange();
	            range.setStart(sel.anchorNode, sel.anchorOffset);
	            range.setEnd(sel.focusNode, sel.focusOffset);
	            var backwards = range.collapsed;
	            range.detach();

	            // modify() works on the focus of the selection
	            var endNode = sel.focusNode, endOffset = sel.focusOffset;
	            sel.collapse(sel.anchorNode, sel.anchorOffset);
	            
	            var direction = [];
	            if (backwards) {
	                direction = ['backward', 'forward'];
	            } else {
	                direction = ['forward', 'backward'];
	            }

	            sel.modify("move", direction[0], "character");
	            sel.modify("move", direction[1], "word");
	            sel.extend(endNode, endOffset);
	            sel.modify("extend", direction[1], "character");
	            sel.modify("extend", direction[0], "word");
	        }
	    } else if ( (sel = document.selection) && sel.type != "Control") {
	        var textRange = sel.createRange();
	        if (textRange.text) {
	            textRange.expand("word");
	            // Move the end back to not include the word's trailing space(s),
	            // if necessary
	            while (/\s$/.test(textRange.text)) {
	                textRange.moveEnd("character", -1);
	            }
	            textRange.select();
	        }
	    }
	}

	function getHTMLOfSelection() {

		var range;

		if (document.selection && document.selection.createRange) {

			range = document.selection.createRange();
			return range.htmlText;
		
		} else if (window.getSelection) {
		
			var selection = window.getSelection();
		
			if (selection.rangeCount > 0) {
		
				range = selection.getRangeAt(0);
				var clonedSelection = range.cloneContents();
				var div = document.createElement('div');
				div.appendChild(clonedSelection);
				return div.innerHTML;

			} else {
		
				return '';

			}
		} else {
			
			return '';

		}
	}

	function withinContent( the_text ) {

	    var inContent = $('.enco-content').html().indexOf( the_text );

	    if( inContent > 0 )
	    	return true;
	    else
	    	return false;
	}

	function isTextInClass( text, css_class ) {

		var result = false;

		$( '.enco-subject' ).each( function( index ) {

			if( $( this ).html().indexOf( text ) != 'undefined' ) {

				var foundAt;

				foundAt = $( this ).html().indexOf( text );

				if( foundAt > 0 ) {
					result = true;
				}

			}

		});
		
		return result;
	}

	function wordCount( text ) {
		
		return text.split(' ').length;
	}

	function getOccurrence( text, e ) {

        var offset 		= $('html').position(),
            x 			= e.clientX - offset.left,
            y 			= e.clientY - offset.top,
            occurrence 	= -1;

		if( $.trim(text).length ){
		
			//wrap each word similar to the highlighted text with <span>
			var regex = new RegExp( text, 'g' );
			$('.enco-content').html(
				$('.enco-content').html().replace( regex, '<span class="enco-temp">' + text + '</span>' )
			);
			
			//get the new span index situating on coords
            var el 			= document.elementFromPoint( x, y );
			var exist 		= $('span.enco-temp').length;
			occurrence 	= $('span.enco-temp').index(el);

			//remove the <span> wrapper            
			$('span.enco-temp').contents().unwrap();

			//alert(exist + ' exist | occurrence: ' + occurrence + '\n\n' + el.innerHTML );

		}

	    return occurrence;
	}

	function enco_overlay_close( e ) {
	    var container = $("#enco-overlay");
	    var isSubject = $(".enco-subject").is(e.target);

	    if ( !container.is(e.target) // if the target of the click isn't the container...
	        && container.has(e.target).length === 0
	        && !isSubject ) // ... nor a descendant of the container
	    {
	        enco_close();
	    }
	}

	function enco_open() {
	
		if( !enco_overlay ) {
			$('#enco-overlay').css( 'display', 'block' );
			$('html').addClass('enco-modal-open');
		}

		enco_overlay = true;
	}

	function enco_close() {
	
		if( enco_overlay ) {
			$('#enco-overlay' ).css( 'display', 'none' );
			$('html').removeClass('enco-modal-open');
			
			enco_hide_threads();
			enco_hide_start_thread();
		}

		enco_overlay = false;
	}

	function enco_show_thread( id ) {
        
        enco_hide_start_thread();
        enco_hide_threads();
    	$('#enco-thread-' + id ).show();
    	
    	if( !enco_overlay )
    		enco_open();
	}

	function enco_hide_threads() {
    
    	$('.enco-overlay .enco-thread:not(.enco-thread.enco-orphan-thread)').hide();
	}

    function enco_show_start_thread( text, occ ) {

    	enco_hide_threads();

		$('#enco-start-thread input#thread_occurrence').val( occ );
		$('#enco-start-thread #enco-thread-subject').val( text );
    	$('#enco-start-thread').show();
    	
    	if( !enco_overlay )
    		enco_open();
	}

    function enco_hide_start_thread() {

		$('#enco-start-thread').hide();
		$('#enco-start-thread input#thread_occurrence').removeAttr('value');
		$('#enco-start-thread #enco-thread-subject').removeAttr('value');
    }

	function enco_reply_link_click( threadId, commentId ) {

		$('#enco-reply-' + threadId).show();
    	$('#enco-reply-' + threadId).appendTo( '#enco-reply-container-id-' + commentId );
    }

    function enco_reply_change_parent( threadId, commentId ) {

    	$('#enco-reply-form-' + threadId + ' #comment_parent').val( commentId );
    }

    function enco_reply_close( threadId ) {

    	enco_reply_change_parent( threadId, 0 );
	    $( '#enco-reply-' + threadId ).insertAfter( '#enco-thread-' + threadId + ' .enco-comment-list' );
    }

	$.br2nl = function(varTest){
	    return varTest.replace(/<br>/g, "\r");
	};

	$.nl2br = function(varTest){
	    return varTest.replace(/(\r\n|\n\r|\r|\n)/g, "<br>");
	};

})( jQuery );
