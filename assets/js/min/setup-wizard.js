!function(t){"use strict";t(window).on("popstate",(function(){location.reload(!0)}));var e="undefined"==typeof setupWizardData?[]:setupWizardData,a=t(".mai-setup-wizard .step"),s=function(t){for(var e=window.location.search.substring(1).split("&"),a=!1,s=0;s<e.length;s++)if((a=e[s].split("="))[0]===t)return void 0===a[1]?"welcome":decodeURIComponent(a[1])}("page"),i=function(e,a,s){void 0!==s&&(s=300),a.each((function(){var a=t(this);a.attr("id")!==e?a.fadeOut(0):setTimeout((function(){a.fadeIn(s)}),s)}))};i(e.currentStep,a,0),a.each((function(){var e={object:t(this),id:t(this).attr("id"),submit:t(this).find("#submit"),skip:t(this).find("#skip"),previous:t(this).find("#previous"),next:t(this).next(a),prev:t(this).prev(a)};e.submit.click((function(){var a=t("#"+e.id+" input:enabled"),s=a.attr("type")?a.attr("type"):"text";"checkbox"!==s&&"radio"!==s||(a=t("#"+e.id+" input:enabled:checked")),e.submit.text(e.submit.attr("data-loading")),t("[data-status]").removeAttr("data-status"),t(a[0]).closest("li").attr("data-status","running"),n(e,a,0)})),e.skip.click((function(){t("[data-status]").removeAttr("data-status"),r(e.next.attr("id"))})),e.previous.click((function(){t("[data-status]").removeAttr("data-status"),r(e.prev.attr("id"))}))}));var n=function(a,s,i){if(t(s[i]).closest("li").attr("data-status","running"),t(s[i-1]).closest("li").attr("data-status","complete"),i>=s.length)setTimeout((function(){r(a.next.attr("id")),a.submit.text(a.submit.attr("data-default"))}),1e3);else{var n=t(s[i]),u=n[0].attributes,c={};"object"==typeof u&&t.each(u,(function(){c[this.name]=this.value})),c.value=n.val(),t.ajax({type:"post",dataType:"json",url:e.ajaxUrl,timeout:3e4,data:{action:"mai_setup_wizard_"+a.id,counter:i,field:c,nonce:e.nonce},success:function(t){setTimeout((function(){o(t,a,s,i,"success")}),1e3)},error:function(t){setTimeout((function(){o(t,a,s,i,"error")}),1e3)}})}},o=function(e,a,s,i,o){var r=e.hasOwnProperty("success")&&e.success&&"success"===o;if(e.hasOwnProperty("status")&&"newAJAX"===e.status)n(a,s,i);else{if(!r&&"error"!==o)return e.hasOwnProperty("data")&&t("#"+a.id+" .error").show().text(e.data),a.submit.text(a.submit.attr("data-default")),t(s[i]).closest("li").removeAttr("data-status"),void console.log(e);console.log(e),i++,n(a,s,i)}},r=function(e){var n=window.location.protocol+"//"+window.location.host+window.location.pathname+"?page="+s+"&step="+e,o=t("#"+e),r=o.next(a).prev(a);o.hasClass("step")&&r.hasClass("step")&&(window.history.pushState({path:n},"",n),i(e,a))},u=function(e){t('[name="plugins"], [name="content"], [name="templates"], [name="customizer"]').each((function(){var a=t(this),s=JSON.parse(a.attr("data-demo")).id,i=e===s,n=e!==s,o=e!==s;a.attr("checked",i),a.prop("disabled",n),a.closest("li").prop("hidden",o)}))};u(e.chosenDemo),t("p.error, p.success").hide(),t('[name="demo"]').click((function(){u(t(this).val())}))}(jQuery);