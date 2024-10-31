/**
 * Admin side script for Repost-Content plugin.
 **/

//Requires jQuery
(function(d,w,undef) {
    jQuery(document).ready(function($) {
        var searchParam = "";
	var searchText = "";
	
	$("body").removeClass("repost-content-loading");
	/**
	 * Welcome pointer
	 **/
	/** Check that pointer support exists AND that text is not empty */
	if(typeof(jQuery().pointer) != 'undefined' && repost_content_ajax_object.pointers.length) {
	    for (var p in repost_content_ajax_object.pointers) {
		var ptr = repost_content_ajax_object.pointers[p];
		doPointer(ptr);
	    }
	}
	
	//NB ptr is in closure so the callback gets the right one
	function doPointer(ptr) {
	    jQuery(ptr.pointerTarget).pointer({
		    content    : ptr.pointerText,
		    close  : function() {
			    jQuery.post( ajaxurl, {
				    pointer: ptr.pointerHandle,
				    action: 'dismiss-wp-pointer'
			    });
		    }
	    }).pointer('open');
	}
        
        /**
         * Embedded search stuff
         **/
        $("#repost-content-searchframe").each(function() {
            var srcEl = this;
	    var tgt = $(this).attr('src');
            
            
            
            //Setup to receive messages
            $.receiveMessage(function(e) {
                if (e.data.match(/repostHeight=/)) {
                    $(srcEl).height(e.data.replace(/repostHeight=/,""));
                }
		if (e.data.match(/repostScroll/)) {
		    window.scrollTo(0,0);
		}
                if (e.data == "repostAlive") {
                    var msg = {};
                   
                    $.postMessage("repostPlugin", tgt, srcEl.contentWindow);
                }
                if (e.data.match(/^repostSearch=/)) {
                    searchParam = e.data.replace(/^repostSearch=/,"");
                }
                if (e.data.match(/^repostArticle=/)) {
                    var opts = {
                        "title": 0,
                        "pid": e.data.replace(/^repostArticle=/,"")
                    };   
                    lauchPreview(opts);
                }
		if (e.data.match(/^repostSearchText=/)) {
		    searchText = e.data.replace(/^repostSearchText=/,"");
		}
		
            });
        });
        /**
         * Show/hide
         **/
        $(".repost-content-showhide").click(function(e) {
	    e.preventDefault();
            $(this).closest(".repost-content-feed").toggleClass("repost-content-feed-hide");
	    return false;
        });
	
	/**
	 * Search within
	 **/
	$(".repost-content-refine").click(function(e) {
	    e.preventDefault();
	    $(this).hide();
	    $(this).closest(".repost-content-feed").addClass("repost-content-refine-show");
	    return false;
	});
	
	
	$(".repost-content-add-search-submit").click(function(e) {
	    e.preventDefault();
	    var $scope = $(this).closest(".repost-content-feed");
	    var q = $(".repost-content-add-search",$scope).val();
	    if (q.length == 0) {
		return false;	//No term given so ignore
	    }
	    //OK we have a search term
	    $(".repost-content-itemlist",$scope).fadeTo(100,0.2);
	    $(".repost-content-more",$scope).remove();
	    
	    doFeed(0,$scope,q);
	    return false;
	});
        /**
         * Feedloader
         *
         * Iterate over feeds loading contet for each via ajax.
         **/
        $(".repost-content-feed").each(doFeed);
				       
	function doFeed(idx,$feed,q) {
            var fid = $($feed).attr('id');
            //var $feed = feed;
            var opts = {
                "count" : parseInt(repost_content_ajax_object.count),
                "start" : 0
            };
            
            if (document.location.hash.match(/^#repost-content-feed-/) && document.location.hash != "#"+fid) {
                $("#"+fid).addClass("repost-content-feed-hide");
            }
            
            
            function getItems() {
                var json = "jsoncallback=?";
                if(repost_content_feeds[fid].match(/\?/)) {
                    json = "&" +json;
                } else {
                    json = "?" +json;
                }
		
		//use protocol agnostic urls so httpds doesn't bite us.
		repost_content_feeds[fid] = repost_content_feeds[fid].replace(/^http:/,"");

                
		//Pass extra search param
		if (q != undef) {
		    opts.qq = q;
		}

                $.getJSON(repost_content_feeds[fid] + json, opts, function(data) {
		    
                    $(".repost-content-itemlist",$feed).fadeTo(0,1); //We might be faded
                    if($(".repost-content-more",$feed).size()) {
                        $(".repost-content-more",$feed).remove();
                    } else {
                        $(".repost-content-itemlist",$feed).empty();
		    }
                    
		    
                    
                    if(data.message) {
                        $(".repost-content-itemlist",$feed).append("<p>" + data.message + "</p>");
                        return;
                    }
                    //This should be an article list
                    while (data.length) {
                        var doc = data.shift(),
                            $template = $("#repost-content-article-result-template").clone().removeAttr('id');
                            
                        if ($('#repost-content-feed-' + fid + ' .repost-content-doc-'+doc.id).size()) {
                            continue;
                        }
                        
                        $template.addClass('repost-content-doc-'+doc.id);
                        $(".repost-content-article-title a",$template).html(doc.title).click(showPreview).data('id',doc.id);
                        $('.repost-content-article-date',$template).text(doDate(doc.timestamp_i));
                        $('.repost-content-article-source',$template).text(doc.site_text);
                        'thumb_display_s' in doc ? $template.find('.repost-content-article-image img').attr('src',doc.thumb_display_s) : $template.find('.repost-content-article-image').remove();
                        
                        $('.repost-content-article-excerpt',$template).text(doc.body_text);
                        $(".repost-content-itemlist",$feed).append($template);
                        $template.fadeTo(200,0.7);
                        donePost(doc.id);
                        $template.show();
                    }
                    $(".repost-content-itemlist",$feed).append($("#repost-content-more-template").clone().show().removeAttr('id').click(function(e) {
                        e.preventDefault();
                        opts.start = opts.start + opts.count;
                        opts.count = 10;
                        getItems();
                        return false;
                    }));
                });
            }
            getItems();
            
        };
        getCounts();
        
        $("#repost-content-widget").each(function() {
            var req = {};
            var feeds = [];
            
            $(".repost-content-widget-feed-count").each(function() {
                feeds.push($(this).attr('id').replace(/repost-content-widget-feed-count-/,""));
            });
            req.feeds = feeds.join(",");
            
            req.nonce = repost_content_ajax_object.nonce;
            req.action = "repost_content_whats_new";
            $.post(repost_content_ajax_object.ajax_url, req, function(response) {
               for (var feed_id in response) {
                if (response[feed_id] > 0) {
                    $("#repost-content-widget-feed-count-"+feed_id ).text(response[feed_id]);
                    $("#repost-content-widget-feed-"+feed_id ).removeClass("repost-content-widget-feed-idle");
                    
                }
               }
            });
            
        });
        
        
        //Get new counts for each feed
        function getCounts() {
            var req = {};
            var feeds = [];
            
            $(".repost-content-feed").each(function() {
                feeds.push($(this).attr('id').replace(/repost-content-feed-/,""));
            });
            req.feeds = feeds.join(",");
            //AJAX newcounts
            
            req.nonce = repost_content_ajax_object.nonce;
            req.action = "repost_content_whats_new";
            $.post(repost_content_ajax_object.ajax_url, req, function(response) {
               for (var feed_id in response) {
                if (response[feed_id] > 0) {
                    $("#repost-content-feed-"+feed_id + " h2").append(" <span class=\"repost-content-newcount\">("+response[feed_id]+" new in the last 24h)</span>");
                    $("#repost-content-feed-"+feed_id + " .repost-content-itemlist-count").text(response[feed_id]);
                }
               }
            });
                              
        }
        
        //Have we done this post?
        function donePost(id) {
            var req = {};
            
            //AJAX publish
            req.id = id;
            req.nonce = repost_content_ajax_object.nonce;
            req.action = "repost_content_exists";
            $.post(repost_content_ajax_object.ajax_url, req, function(response) {
                if (response.post_id != 0) {
                    $('.repost-content-doc-'+req.id).fadeTo(200,0.5);
                    $('.repost-content-doc-'+req.id+' .repost-content-preview-button').hide();
                    $('.repost-content-doc-'+req.id+' .repost-content-edit-button').attr('href',response.edit_url).text(response.status);
                    $('.repost-content-doc-'+req.id+' .repost-content-article-title a')
                        .attr('href',response.edit_url)
                        //.after("<span class='repost-content-state'> ("+ response.status +")</span>")
                        .unbind('click',showPreview);
                    $('.repost-content-doc-'+req.id+' .repost-content-edit-button').show();
                } else {
                    $('.repost-content-doc-'+req.id).fadeTo(200,1.0);
                    $('.repost-content-doc-'+req.id+' .repost-content-edit-button').hide();
                    $('.repost-content-doc-'+req.id+' .repost-content-preview-button')
                        .attr('href',$('.repost-content-doc-'+req.id+' .repost-content-article-title a').attr('href'))
                        .data("id",req.id)
                        .click(showPreview);
                    $('.repost-content-doc-'+req.id+' .repost-content-preview-button').show();
                }
            });
        }
        
        //Render a unix datestamp in a local time
        function doDate(stamp) {
            var d = new Date,
                now = new Date,
                yesterday = new Date(now.getTime() - 86400 * 1000),
                age;

            d.setTime(stamp * 1000);
            age = (now.getTime() - d.getTime()) / (60 * 1000);
            if (age < 0 ) {
                    return "Today";
            }
            if (age < 60) {
                    return parseInt(age) + " Minutes Ago";
            }
            if (age < 60 * 2) {
                    return parseInt(age / 60) + " Hour Ago"
            }
            if (age < 60 * 18) {
                    return parseInt(age / 60) + " Hours Ago"
            }


            if (now.toLocaleDateString() == d.toLocaleDateString()) {
                    return "Today";
            } else if (yesterday.toLocaleDateString() == d.toLocaleDateString()) {
                    return "Yesterday";
            } else {
                    return d.toDateString().replace(/^\w*\s/, "");
            }
        }
        
        function showPreview(e) {
            e.preventDefault();
            
            var opts = {
                "title":    0,
                "pid":      $(this).data('id')
            };
            
            lauchPreview(opts);
            return false;
        }
        
        function lauchPreview(opts) {
            var api = repost_content_ajax_object.api_url + "/getEmbed";
            $("#repost-content-preview-message").html("Loading").append($(".repost-content-loader-gif").clone().show());
            
            $("body").addClass("repost-content-loading");
            $("#repost-content-preview input").data("id",opts.pid);
            
            window.scrollTo(0,0);
            $.getJSON(api+"?jsoncallback=?",opts,function(data) {
                $("body").removeClass("repost-content-loading");
                $("#repost-content-preview").show();
                $("#repost-content-preview-embed").html(data.embed).data(data);
                $("#repost-content-title-input").val($("#repost-content-preview-embed .rpuTitle").text());   
            }).fail(function() {
                $("#repost-content-preview-message").html("Error - Unable to Load Articvle");
            })
            .always(function() {
                setTimeout(function() {
                    $("body").removeClass("repost-content-loading");
                },2000);
            });
           
        }
        
        function publishPost(id,type, title) {
            var req = {};
            
            //AJAX publish
            req.title = title;
            req.nonce = repost_content_ajax_object.nonce;
            req.id = id;
            req.action = "repost_content_"+type;
            
            $("#repost-content-preview").hide();
            $("#repost-content-preview-embed").empty();
            $("#repost-content-title-input").val("");
            $("#repost-content-preview-message").html("Saving Please Wait").append($(".repost-content-loader-gif").clone().show());
            
            $("body").addClass("repost-content-loading");
            $.post(repost_content_ajax_object.ajax_url, req, function(response) {
                
                if (response.go != null) {
                    $("#repost-content-preview-message").html("Loading Post").append($(".repost-content-loader-gif").clone().show());
                    document.location = response.go ;
                    return;
                    //not reached
                }
                $("#repost-content-preview-message").html("Complete");
                donePost(req.id);
            })
            .fail(function() {
                $("#repost-content-preview-message").html("Post Failed");
            })
            .always(function() {
                setTimeout(function() {
                    $("body").removeClass("repost-content-loading");
                },1000);
            });
        }
        
        /**
         * Attach actionsto things
         **/
        
        $("#repost-content-post-cancel").click(function(e) {
            e.preventDefault();
            $("#repost-content-preview-embed").empty();
            $("#repost-content-title-input").val("");
            $("#repost-content-preview").hide(); 
        });
        
        $("#repost-content-post-publish").click(function(e) {
            e.preventDefault();
            publishPost($(this).data('id'),'post',$("#repost-content-title-input").val());
            return false;
        });
        
        $("#repost-content-post-draft").click(function(e) {
            e.preventDefault();
            publishPost($(this).data('id'),'draft',$("#repost-content-title-input").val());
            return false;
        });
        
        

        
        $(".repost-content-save-search").click(function(e) {
            e.preventDefault();
            $("#repost-content-save-search-error").empty();
            $("#repost-content-save-search").show();
            
            //var param = window.decodeURIComponent(searchParam);
            //var param = param.split(/&/);
            //var params = {};
            //for (var p in param) {
            //    var pv = param[p].split(/=/);
            //    params[pv[0]] = pv[1].replace(/\+/g," ");
            //}
            //if("q" in params) {
            //    $("#repost-content-save-search-name").val(params.q);
            //}
	    $("#repost-content-save-search-name").val(decodeURIComponent(searchText).replace(/\+/g," "));
            return false;
        });
        
        $("#repost-content-save-search-commit").click(function(e) {
            e.preventDefault();
            var req = {};
            
            //AJAX publish
            req.name = $("#repost-content-save-search-name").val();
            req.nonce = repost_content_ajax_object.nonce;
            req.search = searchParam;
            req.action = "repost_content_add_custom";
            
            if (req.name.length == 0) {
                $("#repost-content-save-search-error").html("Please enter a name");
                return false;
            }
            $.post(repost_content_ajax_object.ajax_url, req, function(response) {
                $("#repost-content-save-search-error").html(response.message);
                if (!response.error) {
                    setTimeout(function() {
                        $("#repost-content-save-search").hide();
                        $("#repost-content-save-search-error").empty();
                    },1000)
                }
            })
            .always(function() {
                setTimeout(function() {
                     $("#repost-content-save-search").hide();
                    $("#repost-content-save-search-error").empty();
                },2000);
            });
            return false;
        });
        
        
        $("#repost-content-save-search-cancel").click(function(e) {
            e.preventDefault();
            $("#repost-content-save-search").hide();
            return false;
        });
        
        
        
    });
    
})(document,window);




/* Save a server trip by including this here */
/*
 * jQuery postMessage - v0.5 - 9/11/2009
 * http://benalman.com/projects/jquery-postmessage-plugin/
 * 
 * Copyright (c) 2009 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function($) {
    var g, d, j = 1,
	a, b = this,
	f = !1,
	h = "postMessage",
	e = "addEventListener",
	c, i = b[h];
    $[h] = function(k, l, m) {
	if (!l) {
	    return
	}
	k = typeof k === "string" ? k : $.param(k);
	m = m || parent;
	if (i) {
	    m[h](k, l.replace(/([^:]+:\/\/[^\/]+).*/, "$1"))
	} else {
	    if (l) {
		m.location = l.replace(/#.*$/, "") + "#" + (+new Date) + (j++) + "&" + k
	    }
	}
    };
    $.receiveMessage = c = function(l, m, k) {
	if (i) {
	    if (l) {
		a && c();
		a = function(n) {
		    if ((typeof m === "string" && n.origin !== m) || ($.isFunction(m) && m(n.origin) === f)) {
			return f
		    }
		    l(n)
		}
	    }
	    if (b[e]) {
		b[l ? e : "removeEventListener"]("message", a, f)
	    } else {
		b[l ? "attachEvent" : "detachEvent"]("onmessage", a)
	    }
	} else {
	    g && clearInterval(g);
	    g = null;
	    if (l) {
		k = typeof m === "number" ? m : typeof k === "number" ? k : 100;
		g = setInterval(function() {
		    var o = document.location.hash,
			n = /^#?\d+&/;
		    if (o !== d && n.test(o)) {
			d = o;
			l({
			    data: o.replace(n, "")
			})
		    }
		}, k)
	    }
	}
    }
})(jQuery);