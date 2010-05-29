<?php /*quick and useful benchmarking static class
copyright Ryan Day 2010
http://ryanday.org

lgpl

USE:
bench::mark('all');
sleep(1);
bench::mark('app');
sleep(3);
bench::end('app');
sleep(1);
bench::endAll();

echo bench::total('app')." seconds spent in app";
echo bench::total('all')." seconds spent in all";
*/
class bench{
        private static $marks = array();
        private static $mark_structure = array('total'=>0,'start'=>0);

        public static function mark($name,$start = null){
                self::verify($name);
                $time = microtime(true);
                if(isset($start)) $time = $start;
                self::$marks[$name]['start'] = $time;
        }

        public static function end($name) {
                if(isset(self::$marks[$name])){
                        if(self::$marks[$name]['start']){
                                self::$marks[$name]['total'] += microtime(true)-self::$marks[$name]['start'];
                                self::$marks[$name]['start'] = 0;
                        }
                }
        }

        public static function total($name){
                self::verify($name);
                return self::$marks[$name]['total'];
        }

        public static function endAll(){
                foreach(self::$marks as $name=>&$data){
                        if($data['start']){
                                $data['total'] += microtime(true)-$data['start'];
                                $data['start'] = 0;
                        }
                }
        }

        private static function verify($name){
                if(!isset(self::$marks[$name])){
                        self::$marks[$name] = self::$mark_structure;
                }
        }
}