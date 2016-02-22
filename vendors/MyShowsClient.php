<?php
    /**
     * MyShows API Client for v1.5
     * @see http://api.myshows.ru/
     * @package MyShows
     * @version 1.1
     */
    class MyShowsClient {

        /** Http Status Code 200 */
        const HttpOk = 200;

        /** Http Status Code 404 */
        const HttpNotFound = 404;

        /** Http Status Code 403 */
        const HttpForbidden = 403;

        /** Http Status Code 500 */
        const HttpServiceError = 500;

        /**
         * Male
         */
        const Male = 'male';

        /**
         * Female
         */
        const Female = 'female';


        /***
         * Profile Scheme
         */
        const ProfileScheme = 'profile';

        /**
         * Public Scheme
         */
        const PublicScheme  = 'public';

        /**
         * Show Status "Watching"
         */
        const StatusWatching = 'watching';

        /**
         * Show Status "Later"
         */
        const StatusLater = 'later';

        /**
         * Show Status "cancelled"
         */
        const StatusCancelled = 'cancelled';

        /**
         * Show Status "remove"
         */
        const StatusRemove = 'remove';

        /**
         * Show Statuses
         * @var array
         */
        public static $ShowStatuses = array( self::StatusWatching, self::StatusLater, self::StatusCancelled, self::StatusRemove );

        /**
         * PHP Session Id
         * @var string
         */
        private $phpSessionId;

        /**
         * API Host
         * @var string
         */
        public $Host = 'http://api.myshows.ru';

        /**
         * Url Mapping
         * @var array
         */
        private static $mapping = array(
            self::ProfileScheme => array(
                'login'                  => '/profile/login?login=%s&password=%s'
                , 'shows'                => '/profile/shows/'
                , 'watched-episodes'     => '/profile/shows/%d/'
                , 'next-episodes'        => '/profile/episodes/next/'
                , 'unwatched-episodes'   => '/profile/episodes/unwatched/'
                , 'check-episode'        => '/profile/episodes/check/%d'
                , 'check-episode-rating' => '/profile/episodes/check/%d?rating=%d'
                , 'uncheck-episode'      => '/profile/episodes/uncheck/%d'
                , 'rate-episode'         => '/profile/episodes/rate/%d/%d'
                , 'sync-episodes'        => '/profile/shows/%d/sync?episodes=%s'
                , 'sync-episodes-delta'  => '/profile/shows/%d/episodes?check=%s&uncheck=%s'
                , 'show-status'          => '/profile/shows/%d/%s'
                , 'show-rating'          => '/profile/shows/%d/rate/%d'
                , 'favorites-list'       => '/profile/episodes/favorites/list/'
                , 'favorites-add'        => '/profile/episodes/favorites/add/%d'
                , 'favorites-remove'     => '/profile/episodes/favorites/remove/%d'
                , 'ignored-list'         => '/profile/episodes/ignored/list/'
                , 'ignored-add'          => '/profile/episodes/ignored/add/%d'
                , 'ignored-remove'       => '/profile/episodes/ignored/remove/%d'
                , 'friends-news'         => '/profile/news/'
            )
            , self::PublicScheme => array(
                'search-show'            => '/shows/search/?q=%s'
                , 'search-file'          => '/shows/search/file/?q=%s'
                , 'show-info'            => '/shows/%d'
                , 'genres'               => '/genres/'
                , 'shows-top'            => '/shows/top/%s/'
                , 'profile'              => '/profile/%s'
            )
        );

        /**
         * Curl Options
         */
        protected $curlOptions = array(
            CURLOPT_RETURNTRANSFER   => true
            , CURLOPT_HEADER         => false
            , CURLOPT_TIMEOUT        => 10
        );

        /**
         * MyShows Login
         * @var string
         */
        protected $login;

        /**
         * MyShows Password
         * @var string
         */
        protected $password;

        /**
         * Throw Exceptions on connection error or http status code 502 or 503
         * @var bool  default false
         */
        public $ThrowExceptions;

        /**
         * @param  string $login
         * @param  string $password
         * @param bool $encodePassword  encode password by default
         * @param bool $throwExceptions  throw exceptions on connection error
         */
        function __construct( $login = null, $password = null, $encodePassword = true, $throwExceptions = false  )
        {
            $this->login           = $login;
            $this->password        = $encodePassword ? md5( $password ) : $password;
            $this->ThrowExceptions = $throwExceptions;
        }


        /**
         * Send Request
         * @static
         * @param  string $url
         * @param array $options
         * @return array  (status, json, output, error)
         */
        protected function getUrl( $url, $options = array() ) {
            $ch  = curl_init();
            if( is_array( $options ) ) {
                $options += $this->curlOptions;
            } else {
                $options = $this->curlOptions;
            }

            $options[CURLOPT_URL] = $this->Host . $url;
            curl_setopt_array( $ch, $options );

            $output = curl_exec( $ch );
            $info   = curl_getinfo( $ch );

            if ( $this->ThrowExceptions ) {
                if ( $output === false ) {
                    throw new Exception( curl_error( $ch ), curl_errno( $ch ) );
                }

                if ( in_array( $info['http_code'],  array( 502, 503 ) ) ) {
                    curl_close( $ch );
                    throw new Exception( 'Server error: ' . $info['http_code'], $info['http_code'] );
                }
            }

            curl_close( $ch );

            return array(
                'status'   => $info['http_code']
                , 'json'   => !empty( $output ) ? json_decode( $output ) : null
                , 'output' => $output
                , 'error'  => $output === false
                , 'url'    => $options[CURLOPT_URL]
            );
        }


        /**
         * Send Request to MyShows API Server
         * @param string $scheme
         * @param string $action
         * @param mixed $param1
         * @param mixed $_
         * @return array of (status, json, output, error)
         */
        protected function sendRequest( $scheme, $action, $param1 = null, $_ = null ) {
            $result = array(
                'status'   => null
                , 'json'   => null
                , 'output' => null
                , 'error'  => true
            );

            if ( empty( $scheme ) || empty( $action ) || empty( self::$mapping[$scheme] ) || empty( self::$mapping[$scheme][$action ] ) ) {
                return $result;
            }

            $url = self::$mapping[$scheme][$action];
            if ( func_num_args() > 2 ) {
                $args = func_get_args();
                $url  = vsprintf( $url, array_slice( $args, 2  ) );
            }

            $result = $this->getUrl( $url );
            return $result;
        }


        /**
         * Login
         * @return bool
         */
        public function Login() {
            $this->curlOptions[CURLOPT_HEADER] = true;
            $result = $this->sendRequest( self::ProfileScheme, 'login', $this->login, $this->password );
            $this->curlOptions[CURLOPT_HEADER] = false;

            if ( $result['status'] != self::HttpOk ) {
                return false;
            }

            if ( !preg_match( '/PHPSESSID=(.+);/', $result['output'], $matches ) ) {
                return false;
            }

            $this->phpSessionId = $matches[1];
            $this->curlOptions[CURLOPT_COOKIE] = sprintf( 'PHPSESSID=%s;', $this->phpSessionId );

            return true;
        }


        /**
         * Get My Shows
         * @return My_UserShow[]
         */
        public function GetMyShows() {
            $result = $this->sendRequest( self::ProfileScheme, 'shows' );
            if ( $result['status'] == self::HttpOk ) {
                 return $this->getObjectsArray( $result, 'My_UserShow' );
            }

            return false;
        }
        /**
         * Get My Shows
         * @return My_UserShow[]
         */
        public function GetMyShowsId($id) {
            $result = $this->sendRequest( self::ProfileScheme, "shows",$id);
            if ( $result['status'] == self::HttpOk ) {
                return $this->getObjectsArray( $result, 'My_UserShow' );
            }
        
            return false;
        }
        

        /**
         * Get My Watched Episodes
         * @param int $showId
         * @return My_WatchedEpisode[]
         */
        public function GetWatchedEpisodes( $showId ) {
            $result = $this->sendRequest( self::ProfileScheme, 'watched-episodes', $showId );
            if ( $result['status'] == self::HttpOk ) {
                return $this->getObjectsArray( $result, 'My_WatchedEpisode' );
            }

            return false;
        }


        /**
         * Get My Unwatched Aired Episodes
         * @return My_Episode[]
         */
        public function GetUnwatchedEpisodes() {
            $result = $this->sendRequest( self::ProfileScheme, 'unwatched-episodes');
            if ( $result['status'] == self::HttpOk ) {
                return $this->getObjectsArray( $result, 'My_Episode' );
            }

            return false;
        }


        /**
         * Get My Next Unaired Episodes
         * @return My_Episode[]
         */
        public function GetNextEpisodes() {
            $result = $this->sendRequest( self::ProfileScheme, 'next-episodes' );
            if ( $result['status'] == self::HttpOk ) {
                return $this->getObjectsArray( $result, 'My_Episode' );
            }

            return false;
        }


        /**
         * Check Episode
         * @param int $episodeId
         * @param int $rating optional, 1-5
         * @return bool
         */
        public function CheckEpisode( $episodeId, $rating = null ) {
            if ( empty( $episodeId ) ) {
                return false;
            }

            if ( !empty( $rating ) && $rating > 0 && $rating <= 5 ) {
                $result = $this->sendRequest( self::ProfileScheme, 'check-episode-rating', $episodeId, $rating );
            } else {
                $result = $this->sendRequest( self::ProfileScheme, 'check-episode', $episodeId );
            }

            if ( $result['status'] == self::HttpOk ) {
                return true;
            }

            return false;
        }


        /**
         * UnCheck Episode Episode
         * @param int $episodeId
         * @return bool
         */
        public function UnCheckEpisode( $episodeId ) {
            if ( empty( $episodeId ) ) {
                return false;
            }

            $result = $this->sendRequest( self::ProfileScheme, 'uncheck-episode', $episodeId );
            if ( $result['status'] == self::HttpOk ) {
                return true;
            }

            return false;
        }


        /**
         * Sync Episodes for Show Id
         * @param int $showId
         * @param int[] $episodes
         * @return bool
         */
        public function SyncEpisodes( $showId, $episodes ) {
            if ( empty( $showId ) || empty( $episodes ) ) {
                return false;
            }

            $result = $this->sendRequest( self::ProfileScheme, 'sync-episodes', $showId, implode( ',', $episodes ) );
            if ( $result['status'] == self::HttpOk ) {
                return true;
            }

            return false;
        }


        /**
         * Sync Delta Episodes for Show Id
         * @param int $showId
         * @param int[] $checkEpisodeIds
         * @param int[] $uncheckEpisodeIds
         * @return bool
         */
        public function SyncDeltaEpisodes( $showId, $checkEpisodeIds = null, $uncheckEpisodeIds = null ) {
            if ( empty( $showId ) || ( empty( $checkEpisodeIds ) || empty( $uncheckEpisodeIds ) ) ) {
                return false;
            }

            $result = $this->sendRequest( self::ProfileScheme, 'sync-episodes-delta', $showId, implode( ',', $checkEpisodeIds ), implode( ',', $uncheckEpisodeIds ) );
            if ( $result['status'] == self::HttpOk ) {
                return true;
            }

            return false;
        }


        /**
         * Set Show Status
         * @param int $showId
         * @param string $status  {@see MyShowsClient::$ShowStatuses}
         * @return bool
         */
        public function SetShowStatus( $showId, $status ) {
            if ( empty( $showId ) || empty( $status ) || ! in_array( $status, self::$ShowStatuses ) ) {
                return false;
            }

            $result = $this->sendRequest( self::ProfileScheme, 'show-status', $showId, $status );
            if ( $result['status'] == self::HttpOk ) {
                return true;
            }

            return false;
        }


        /**
         * Set Show Rating
         * @param int $showId
         * @param int $rating [1-5]
         * @return bool
         */
        public function SetShowRating( $showId, $rating ) {
            if ( empty( $showId ) || empty( $rating ) || abs( $rating ) > 5 ) {
                return false;
            }

            $result = $this->sendRequest( self::ProfileScheme, 'show-rating', $showId, $rating );
            if ( $result['status'] == self::HttpOk ) {
                return true;
            }

            return false;
        }


        /**
         * Set Episode Rating
         * @param int $episodeId
         * @param int $rating [1-5]
         * @return bool
         */
        public function SetEpisodeRating( $episodeId, $rating ) {
            if ( empty( $episodeId ) || empty( $rating ) || abs( $rating ) > 5 ) {
                return false;
            }

            $result = $this->sendRequest( self::ProfileScheme, 'rate-episode', $rating, $episodeId );
            if ( $result['status'] == self::HttpOk ) {
                return true;
            }

            return false;
        }


        /**
         * Get Favorite Episodes
         * @return int[]
         */
        public function GetFavoriteEpisodes() {
            $result = $this->sendRequest( self::ProfileScheme, 'favorites-list' );
            if ( $result['status'] == self::HttpOk ) {
                 return $this->getIntArray( $result );
            }

            return false;
        }


        /**
         * Get Favorite Episodes
         * @return int[]
         */
        public function GetIgnoredEpisodes() {
            $result = $this->sendRequest( self::ProfileScheme, 'ignored-list' );
            if ( $result['status'] == self::HttpOk ) {
                 return $this->getIntArray( $result );
            }

            return false;
        }


        /**
         * Manage Episodes in UserList
         * @param  string $action   action: favorites-add, favorites-remove, ignored-add, ignored-remove
         * @param  int $episodeId
         * @return bool
         */
        private function manageEpisodeInUserList( $action, $episodeId ) {
            if ( empty( $episodeId ) || ! in_array( $action, array( 'favorites-add', 'favorites-remove', 'ignored-add', 'ignored-remove' ) ) ) {
                return false;
            }

            $result = $this->sendRequest( self::ProfileScheme, $action, $episodeId );
            if ( $result['status'] == self::HttpOk ) {
                 return ( bool ) $result['json'];
            }

            return false;
        }


        /**
         * Add Episode to Favorites
         * @param int $episodeId
         * @return bool
         */
        public function AddEpisodeToFavorites( $episodeId ) {
            return $this->manageEpisodeInUserList( 'favorites-add', $episodeId );
        }


        /**
         * Remove Episode from Favorites
         * @param int $episodeId
         * @return bool
         */
        public function RemoveEpisodeFromFavorites( $episodeId ) {
            return $this->manageEpisodeInUserList( 'favorites-remove', $episodeId );
        }


        /**
         * Add Episode to Ignored
         * @param int $episodeId
         * @return bool
         */
        public function AddEpisodeToIgnored( $episodeId ) {
            return $this->manageEpisodeInUserList( 'ignored-add', $episodeId );
        }


        /**
         * Remove Episode from Ignored
         * @param int $episodeId
         * @return bool
         */
        public function RemoveEpisodeFromIgnored( $episodeId ) {
            return $this->manageEpisodeInUserList( 'ignored-remove', $episodeId );
        }


        /**
         * Get Friend News grouped by Date
         * @return array
         */
        public function GetFriendNews() {
            $result = $this->sendRequest( self::ProfileScheme, 'friends-news' );
            if ( $result['status'] == self::HttpOk ) {
                $news = array();
                foreach( $result['json'] as $date => $entries ) {
                    $news[$date] = $this->getObjectsArray( array( 'json' => $entries ), 'My_UserNews' );
                }

                return $news;
            }

            return false;
        }


        /**
         * Get Genres
         * @return My_Genre[]
         */
        public function GetGenres() {
            $result = $this->sendRequest( self::PublicScheme, 'genres' );
            if ( $result['status'] == self::HttpOk ) {
                return $this->getObjectsArray( $result, 'My_Genre' );
            }

            return false;
        }


        /**
         * Search Shows
         * @param string $query  utf-8 query
         * @return My_Show[]
         */
        public function SearchShow( $query ) {
            if ( empty( $query ) ) {
                return false;
            }

            $query  = urlencode( trim( $query ) );
            $result = $this->sendRequest( self::PublicScheme, 'search-show', $query );
            if ( $result['status'] == self::HttpOk ) {
                return $this->getObjectsArray( $result, 'My_Show' );
            } else if ( $result['status'] == self::HttpNotFound ) {
                return array();
            }

            return false;
        }


        /**
         * Get Top Shows
         * @param string $gender all|self::Male|self::Female
         * @return My_Show[]
         */
        public function GetTopShows( $gender = 'all' ) {
            if ( !in_array( $gender, array( 'all', self::Male, self::Female ) ) ) {
                $gender = 'all';
            }

            $result = $this->sendRequest( self::PublicScheme, 'shows-top', $gender );
            if ( $result['status'] == self::HttpOk ) {
                return $this->getObjectsArray( $result, 'My_Show' );
            }

            return false;
        }


        /**
         * Search Episodes by Filename
         * @param string $filename  utf-8 filename
         * @return My_FileSearchResult
         */
        public function SearchEpisodesByFile( $filename ) {
            if ( empty( $filename ) ) {
                return false;
            }

            $query  = urlencode( trim( $filename ) );
            $result = $this->sendRequest( self::PublicScheme, 'search-file', $query );
            if ( $result['status'] == self::HttpOk ) {
                $searchResult = (array) $result['json'];
                /** @var $searchResult My_FileSearchResult*/
                self::ConvertArrayToObject( $searchResult, null, 'My_FileSearchResult' );
                $searchResult->show = $this->convertArrayToShow( $searchResult->show );

                return $searchResult;
            } else if ( $result['status'] == self::HttpNotFound ) {
                return null;
            }

            return false;
        }


        /**
         * Get Show By Id
         * @param  int $showId
         * @return My_Show
         */
        public function GetShowById( $showId ) {
            if ( empty( $showId ) ) {
                return false;
            }

            $result = $this->sendRequest( self::PublicScheme, 'show-info', $showId );
            if ( $result['status'] == self::HttpOk ) {
                return $this->convertArrayToShow(  $result['json'] );
            } else if ( $result['status'] == self::HttpNotFound ) {
                return null;
            }

            return false;
        }


        /**
         * Return Current Profile
         * @return My_Profile
         */
        public function GetMyProfile() {
            return $this->GetProfile( $this->login );
        }


        /**
         * Get Show By Id
         * @param string $login
         * @return My_Profile
         */
        public function GetProfile( $login ) {
            if ( empty( $login ) ) {
                return false;
            }

            $login  = urlencode( trim( $login ) );
            $result = $this->sendRequest( self::PublicScheme, 'profile', $login );
            if ( $result['status'] == self::HttpOk ) {
                $user = (array) $result['json'];
                /** @var $user My_Profile */
                self::ConvertArrayToObject( $user, null, 'My_Profile' );
                $user->gender = $user->getGender();
                foreach( array( 'friends', 'followers' ) as $key ) {
                    if ( !empty( $user->$key ) ) {
                        $user->$key = $this->getObjectsArray( array( 'json' =>  $user->$key ), 'My_Profile' );
                        foreach( $user->$key as $profile ) {
                            $profile->gender = $profile->getGender();
                        }
                    }
                }

                if ( !empty( $user->stats ) ) {
                    self::ConvertArrayToObject( $user->stats, null, 'My_ProfileStats' );
                }

                return $user;
            } else if ( $result['status'] == self::HttpNotFound ) {
                return null;
            }

            return false;
        }


        /**
         * Convert Array to Show
         * @param  array $result my_show stdobject
         * @return My_Show
         */
        protected function convertArrayToShow( $result ) {
            if ( empty( $result ) ) {
                return null;
            }

            $show = (array) $result;

            /** @var $show My_Show */
            self::ConvertArrayToObject( $show, null, 'My_Show' );
            if ( !empty( $show->episodes ) ) {
                $show->episodes = $this->getObjectsArray( array( 'json' =>  $show->episodes ), 'My_Episode' );
                foreach( $show->episodes as $episode ) {
                    /** @var $episode My_Episode */
                    $episode->episodeId = $episode->id;
                    $episode->showId    = $show->id;
                }
            }

            return $show;
        }


        /**
         * Get Int Array
         * @param  array $result  result from {@see $this->sendRequest()}
         * @return int[]
         */
        protected function getIntArray( $result ) {
            if ( empty( $result['json'] ) ) {
                return array();
            }

            $array = array_map( 'intval', $result['json'] );
            return array_combine( $array, $array );
        }


        /**
         * Get Objects Array (cast stdobject[] to type[])
         * @param  array $result result from {@see $this->sendRequest()}
         * @param  string $type  object type
         * @return object[]  array of $type
         */
        protected function getObjectsArray( $result, $type ) {
            if ( empty( $result['json'] ) ) {
                return array();
            }

            $array = $result['json'];
            settype( $array, 'array' );
            array_walk( $array, array( 'MyShowsClient', 'ConvertArrayToObject' ), $type );

            return array_combine( array_map( 'intval', array_keys( $array ) ), array_values( $array ) );
        }


        /**
         * Convert Array to Object
         * @static
         * @param  array $source
         * @param  mixed $sourceKey
         * @param  string $type
         * @return object
         */
        public static function ConvertArrayToObject( &$source, $sourceKey, $type) {
            $result = new $type;
            foreach( $source as $key => $value ) {
                $result->$key = $value;
            }

            $source = $result;
        }
    }


    /**
     * UserShow
     * @author sergeyfast
     * @version 1.0
     */
    class My_UserShow {

        /** @var int */
        public $showId;

        /** @var string */
        public $title;

        /** @var string */
        public $ruTitle;

        /** @var int minutes */
        public $runtime;

        /** @var string */
        public $showStatus;

        /** @var string */
        public $watchStatus;

        /** @var int */
        public $watchedEpisodes;

        /** @var int */
        public $totalEpisodes;

        /** @var int */
        public $rating;
    }


    /**
     * My Watched Episode
     * @author sergeyfast
     * @version 1.0
     */
    class My_WatchedEpisode {

        /** @var int episodeId */
        public $id;

        /** @var string */
        public $watchDate;
    }


    /**
     * Episode
     * @author sergeyfast
     * @version 1.0
     */
    class My_Episode {

        /** @var int */
        public $episodeId;

        /** @var string */
        public $title;

        /** @var int */
        public $showId;

        /** @var int */
        public $seasonNumber;

        /** @var int */
        public $episodeNumber;

        /** @var string */
        public $airDate;

        /** @var string */
        public $productionNumber;

        /** @var int */
        public $sequenceNumber;

        /** @var int episodeId */
        public $id;

        /** @var string tvrage.com episode link description */
        public $tvrageLink;

        /** @var string image url */
        public $image;

        /** @var string sXXeXX */
        public $shortName;
    }


    /**
     * User News Entry
     * @author sergeyfast
     * @version 1.0
     */
    class My_UserNews {

        /** @var int */
        public $episodeId;

        /** @var int */
        public $showId;

        /** @var string show title */
        public $show;

        /** @var string episode title */
        public $title;

        /** @var string */
        public $login;

        /** @var string m|f */
        public $gender;

        /** @var int  total checked episodes */
        public $episodes;

        /** @var string sXXeYY */
        public $episode;

        /** @var string action watch */
        public $action;
    }


    /**
     * Genre
     * @author sergeyfast
     * @version 1.0
     */
    class My_Genre {

        /** @var int */
        public $id;

        /** @var string */
        public $title;

        /** @var string */
        public $ruTitle;
    }


    /**
     * Show
     * @author sergeyfast
     * @version 1.0
     */
    class My_Show {

        /** @var int show id */
        public $id;

        /** @var string */
        public $title;

        /** @var string */
        public $ruTitle;

        /** @var string */
        public $status;

        /** @var string */
        public $country;

        /** @var string Mon/Date/Year, Mon/Year, Year */
        public $started;

        /** @var string Mon/Date/Year, Mon/Year, Year */
        public $ended;

        /** @var int */
        public $year;

        /** @var int */
        public $kinopoiskId;

        /** @var int */
        public $tvrageId;

        /** @var int */
        public $imdbId;

        /** @var int */
        public $watching;

        /** @var int */
        public $voted;

        /** @var float */
        public $rating;

        /** @var int */
        public $runtime;

        /** @var array */
        public $genres;

        /** @var My_Episode */
        public $episodes;

        /** @var int search/top current place */
        public $place;
    }


    /**
     * FileSearch Result
     * @author sergeyfast
     * @version 1.0
     */
    class My_FileSearchResult {

        /** @var int full match - 100, partial match - 85 */
        public $match;

        /** @var string */
        public $filename;

        /** @var int bytes */
        public $filesize;

        /** @var My_Show */
        public $show;
    }


    /**
     * MyShows Profile
     * @author sergeyfast
     * @version 1.0
     */
    class My_Profile {

        /** @var string */
        public $login;

        /** @var string avatar url */
        public $avatar;

        /** @var int hours */
        public $wastedTime;

        /** @var male|female */
        public $gender;

        /** @var My_Profile[] */
        public $friends;

        /** @var My_Profile[] */
        public $followers;

        /** @var My_ProfileStats */
        public $stats;

        /**
         * Get Gender
         * @return string
         */
        public function getGender() {
            switch( $this->gender ) {
                case 'm':
                case MyShowsClient::Male;
                    return MyShowsClient::Male;
                case 'f':
                case MyShowsClient::Female;
                    return MyShowsClient::Female;
                default:
                    return null;
            }
        }
    }


    /**
     * MyShows Profile Stats
     * @author sergeyfast
     * @version 1.0
     */
    class My_ProfileStats {

        /** @var float */
        public $watchedHours;

        /** @var float */
        public $remainingHours;

        /** @var int */
        public $watchedEpisodes;

        /** @var int */
        public $remainingEpisodes;

        /** @var float */
        public $watchedDays;

        /** @var float */
        public $remainingDays;
    }
?>