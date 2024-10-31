var RunForCover = function() {
  var tracks   = [];

  // callback function for request for recent tracks feed
  processXml = function(data) {
    // loop through tracks
    jQuery('track', data).each( function() {
      var track = [];
      track.cdcover    = jQuery('image[size="large"]', this).text() || false;
      track.artistname = jQuery('artist', this).text();
      track.artistmbid = jQuery('artist', this).attr('mbid');
      track.name       = jQuery('name', this).text();
      track.url        = jQuery('url', this).text();
      if ('true' == jQuery(this).attr('nowplaying')) {
        track.time = 'listening now';
      } else {
        track.time = getTimeAgo(jQuery('date', this).text(), RunForCover.settings.gmt_offset);
      }
      tracks.push(track);
    });
    display();
  };

  processArtistXml = function(data) {
    jQuery('artist', data).each( function() {
      _mbid   = jQuery('mbid', this).text();
      _name   = jQuery('name', this)[0];
      _name   = jQuery(_name).text();
      _imgurl = jQuery('image[size="large"]', this)[0];
      _imgurl = jQuery(_imgurl).text();
      if ('' == _imgurl) {
        _imgurl = 'http://cdn.last.fm/flatness/catalogue/noimage/cover_medium_new.gif';
      }
      // set the source of the image to the image of the artist
      jQuery('img[rel="' + justLetters(_name) + '"]').each( function() {
        jQuery(this).attr('src', _imgurl);
        jQuery(this).parent().attr('href', _imgurl);
      });
      // stop looping through the artists returned
      return false;
    });
  };

  justLetters = function(str) {
    str = str.toLowerCase();
    var _m = str.match(/[a-z]/gi);
    return _m.join ('');
  }

  display = function() {
    jQuery(tracks).each( function() {
      if (0 == RunForCover.settings.count) {
        return false;
      }
      if (false === this.cdcover) {
        // no cover image? find artist image instead
        var _newImg = jQuery('<img />').attr('src', '').attr('rel', justLetters(this.artistname)).attr('title', this.artistname + ': ' + this.name + " (" + this.time + ")");
        // and go find the image of the artist
        jQuery.get(RunForCover.settings.ajaxuri,
                   { rfc_artist: this.artistname },
                   processArtistXml
                   );
      } else {
        var _newImg = jQuery('<img />').attr('src', this.cdcover).attr('title', this.artistname + ': ' + this.name + " (" + this.time + ")");
      }

      // how to link the image?
      switch(RunForCover.settings.linkto) {
        case 'lightbox':
          _newImg = jQuery('<a />').attr('href', this.cdcover).attr('rel', 'lightbox').append(_newImg);
          break;
        case 'highslide':
          _newImg = jQuery('<a />').attr('href', this.cdcover).click( function() { return hs.expand(this); }).append(_newImg);
          break;
        case 'lastfm':
        default:
          _newImg = jQuery('<a />').attr('href', this.url).append(_newImg);
          break;
      }

      // how to style the image?
      switch(RunForCover.settings.style) {
        case 'imgwithtxt':
          var _table = jQuery('<table />');
          var _tr    = jQuery('<tr />').appendTo(_table);
          // append image to new TD and append that to TR
          _newImg.appendTo(jQuery('<td />').appendTo(_tr));
          // append second TD with track info
          var _tdR   = jQuery('<td><a href="' + this.url + '">' + this.name + '</a><br />' + this.artistname +  '<br /><i>' + this.time + '</i></td>').appendTo(_tr);
          _newImg = _table;
        case 'justimgs':
        default:
          // nuttin'
      }
  
      // attach generated code to ol#runforcover
      var _newLi = jQuery('<li />').appendTo(jQuery('ol#runforcover'));
      // and add HTML to this list item
      _newImg.css('display', 'none').appendTo(_newLi).slideDown("slow");

      RunForCover.settings.count--;
    });
  };

  getTimeAgo = function(_t, gmt_offset) {
    // difference between then and now
    var _diff = new Date() - new Date(_t);
    // take into account the timezone difference
    _diff = _diff - (gmt_offset * 60000 * 60);

    var _d = [];
    // how may years in the difference? not many, I hope ;-)
    _d.ye = parseInt(_diff / (1000 * 60 * 60 * 24 * 365));
    _d.da = parseInt(_diff / (1000 * 60 * 60 * 24)) - (_d.ye * 365);
    _d.ho = parseInt(_diff / (1000 * 60 * 60)) - (_d.ye * 365 * 24) - (_d.da * 24);
    _d.mi = parseInt(_diff / (1000 * 60)) - (_d.ye * 365 * 24 * 60) - (_d.da * 24 * 60) - (_d.ho * 60);

    var _meantime = [];
    if (_d.ye > 0) { _meantime.push(_d.ye + ' year' + getPluralS(_d.ye)); }
    if (_d.da > 0) { _meantime.push(_d.da + ' day' + getPluralS(_d.da)); }
    if (_d.ho > 0) { _meantime.push(_d.ho + ' hour' + getPluralS(_d.ho)); }
    if (_d.mi > 0) { _meantime.push(_d.mi + ' minute' + getPluralS(_d.mi)) };

    // TODO: replace last comma with 'and'
    return _meantime.join(', ') + ' ago';
  }

  getPluralS = function(_c) {
    return (1 == _c) ? '' : 's';
  }

  // public
  return {
    settings: [],


    // to start running for covers
    start: function() {
      // get recent tracks feed from last.fm
      jQuery.get(this.settings.ajaxuri, { rfc_user: this.settings.username, rfc_count: this.settings.count }, processXml);
    }
  };
}();