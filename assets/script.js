jQuery(document).ready(function($) {

    var setCookie = function( name, value, days ) {
        var expires = "";
        if ( days ) {
            var date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (JSON.stringify( value ) || "")  + expires + "; path=/";
    }

    var getCookie = function( name ) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for( var i=0;i < ca.length;i++ ) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }

    if ( typeof olek_vars != 'undefined' && typeof olek_vars.restricted_medias != 'undefined' ) {

        var count = JSON.parse( getCookie( 'play' ) || '{}' ),
            count_flag = {};

        var check_media = function( m_info, $this ) {
            olek_vars.restricted_medias.forEach( function( m ) {
                if ( m.url != m_info.url ) {
                    delete count_flag[ m.url ];
                }
            } );
            if ( ( count[m_info.url] || 0 ) >= m_info.allowed_val && typeof count_flag[m_info.url] == 'undefined' ) {
                location.href = olek_vars.registration_url;
                if ( $this.get(0).tagName.toLowerCase() == 'video' ) {
                    $this.removeAttr('controls');
                }
                $this.removeAttr( 'onclick' );
                return false;
            } else {
                if ( typeof count_flag[m_info.url] == 'undefined' ) {
                    count_flag[m_info.url] = true;
                    count[m_info.url] = (count[m_info.url] || 0) + 1;
                    setCookie( 'play', count, 2 );
                }
            }
            return true;
        };

        olek_vars.restricted_medias.forEach( function( m_info ) {
            var $this = $('video[src*="' + m_info.url + '"], audio[src*="' + m_info.url + '"], input[onclick*="' + m_info.url + '"]');
            if ( $this.length ) {
                if ( ( count[m_info.url] || 0 ) >= m_info.allowed_val ) {
                    $this.removeAttr( 'onclick' ).on('click', function(e) {
                        e.preventDefault();
                        location.href = olek_vars.registration_url;
                    });
                }
                var event_name = 'play focusout';
                if ( 'input' == $this.get(0).tagName.toLowerCase() ) {
                    event_name = 'click';
                }
                $this.on( event_name, function ( e ) {
                    e.preventDefault();
                    var result = check_media( m_info, $this );
                    if ( ! result ) {
                        $this.get(0).pause();
                    }
                } );
            }
        });
    }
});