/* ---------------------------------------------------------------------
Star-the-Post JS
------------------------------------------------------------------------ */

var STP = (function(STP, $) {
  STP.star = {
    postid: null,

    init: function() {
      if ($('.stp--icon').length < 1) return;

      this.postid = $('#star-the-post').data('postid'); // this needs updated for archive pages
      this.bind();
    },

    bind: function() {
      $('.stp--icon').click(this.handleClick);
      $(document.body).on('post-load', this.eventRefresh );

      // we dont want to run the heartbeat actions on archive pages.
      if ($('.stp--icon').length == 1) {
        $(document).on('heartbeat-send', this.requestCount);
        $(document).on('heartbeat-tick', this.checkCount);
      }
    },

    handleClick: function(e) {
      let target = $(e.target).parent();
      if ($('.stp--icon').length > 0) {
        STP.star.postid = target.data('postid');
      }
      STP.star.addPop(target);
      STP.star.updateCount(target);
    },

    eventRefresh: function(e) {
      // unbind and rebind star the post actions
      console.log('unbinding/rebinding star click event handler.');
      $('.stp--icon').unbind('click').click(STP.star.handleClick);
    },

    addPop: function(target) {
      if ($(target).find('.pop').length > 0) {
        var icon_el = $(target).find('.stp--icon');
        var new_icon = icon_el.clone(true);
        icon_el.before(new_icon);
        var classSearch = icon_el.attr('class').replace(' pop', '');
        $(target)
          .find('.' + classSearch + ':last')
          .remove();

        var outline_el = $(target).find('.stp--outline');
        var new_outline = outline_el.clone(true);
        outline_el.before(new_outline);
        var classSearch = outline_el.attr('class').replace(' pop', '');
        $(target)
          .find('.' + classSearch + ':last')
          .remove();
      } else {
        $(target)
          .find('.stp--icon')
          .addClass('pop');
        $(target)
          .find('.stp--outline')
          .addClass('pop');
      }

      // add animation class to social icons
      setTimeout(function() {
        if (STP.social.hasThrobbed.indexOf(STP.star.postid) == -1) {
          STP.social.hasThrobbed.push(STP.star.postid);
          $(target)
            .parent()
            .find('.social-icons')
            .addClass('throb');
        }
      }, 1000);
    },

    updateCount: function(target) {
      current_count = $(target)
        .find('.stp--count')
        .text()
        .replace(/,/g, '');
      $(target)
        .find('.stp--count')
        .countup({
          startVal: parseInt(current_count),
          endVal: parseInt(current_count) + 1,
          duration: 0.2
        });

      var data = {
        action: 'star_click',
        post_id: STP.star.postid
      };
      $.post(STP.ajaxurl, data, res => {
        if (res > 0) {
          console.log('[stp] new count from res:', res);
        }
      });
    },

    requestCount: function(event, data) {
      console.log('[stp] requesting count...');
      data.stp_postid = STP.star.postid;
    },

    checkCount: function(event, data) {
      console.log('[stp] checking count...');
      if (!data.stp_count) {
        return;
      }
      if (data.stp_count != $('.stp--count').text()) {
        var current_count = $('.stp--count')
          .text()
          .replace(/,/g, '');
        $('.stp--count').countup({
          startVal: parseInt(current_count),
          endVal: parseInt(data.stp_count),
          duration: 2
        });
      } else {
        console.log('[stp] count remains the same');
      }
      console.log('[stp] stp count:', data.stp_count);
    }
  };

  STP.social = {
    hasThrobbed: [],
    copiedPreviously: [],
    init: function() {
      if ($('.social-icons').length < 1) return;
      this.bind();
    },
    bind: function() {
      $('.js-copy-url').click(this.sharelinkClick);
    },
    sharelinkClick: function(e) {
      var stp_el = null;
      if ($('.stp--icon').length > 1) {
        stp_el = $(e.target).parents('.post-footer');
        stp_el = $(stp_el).find('.stp');
        STP.star.postid = stp_el.data('postid');
      } else {
        stp_el = $('.stp');
      }
      STP.social.updateCount(stp_el);
      STP.social.stopThrob();
    },

    stopThrob: function() {
      $('.social-icons').removeClass('throb');
    },

    updateCount: function(stp_el) {
      // only count the first copy per page load.
      if ($.inArray(STP.star.postid, STP.social.copiedPreviously) !== -1) return;
      STP.social.copiedPreviously.push(STP.star.postid);

      STP.star.addPop(stp_el);
      current_count = $(stp_el)
        .find('.stp--count')
        .text()
        .replace(/,/g, '');
      $(stp_el)
        .find('.stp--count')
        .countup({
          startVal: parseInt(current_count),
          endVal: parseInt(current_count) + 7,
          duration: 1
        });

      var data = {
        action: 'url_copied',
        post_id: STP.star.postid
      };
      $.post(STP.ajaxurl, data, res => {
        if (res > 0) {
          console.log('[stp] new count from res:', res);
        }
      });
    }
  };

  /**
   * Doc Ready
   */
  $(function() {
    STP.star.init();
    STP.social.init();
  });

  return STP;
})(STP || {}, jQuery);
