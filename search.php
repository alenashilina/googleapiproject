<?php 

class NearestSubwayStations 
{

    var $travelModel = 'walking'; // тип передвижения для расчета времени и расстояния от точки до точки
    var $radius = 2000; // определяем в местрах радиус поиска
    var $type = 'subway_station'; // определяем тип искомых объектов
    var $language = 'ru';
    var $apiKey = 'YOUR_API_KEY'; // ключ API – для корректной работы в панели управления Google API необходимо включить использование дополнительных API
    var $units = 'metric';

    /*
    * Получаем адрес введенный пользователем
    */
    public function getAddress()
    {
        if(isset($_POST['submitAdress'])&& !empty($_POST["submitAdress"])) {   
            $address = urlencode($_POST['address']);
            return $this->address=$address;
        }
    }


    /*
    * Получаем из введенного адреса нужные нам параметры через Google Maps Geocoding API
    */
    public function getGeocode()
    {
        $address = $this->getAddress();
        $language = $this->language;
        $apiKey = $this->apiKey;

        $geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$address.'&language='.$language.'&key='.$apiKey);
        $geocodeOutput= json_decode($geocode);

        if($geocodeOutput->status == 'OK') { 
            $startLat = $geocodeOutput->results[0]->geometry->location->lat; // Широта введенного адреса 
            $startLong = $geocodeOutput->results[0]->geometry->location->lng; // Долгота введенного адреса
            $startPlaceId=$geocodeOutput->results[0]->place_id; // Place ID введенного адреса
            $startAddress=$geocodeOutput->results[0]->formatted_address; // Полный адрес в соответствии с информацией в Google Maps
        } else {
            echo "К сожалению, что-то пошло не так. Возможно, Вы ввели неполный адрес. Если ошибка повторяется, попробуйте связаться с администратором.";
        }

        return array(
            'startPlaceId'=>$startPlaceId,
            'startAddress'=>$startAddress,
            'startLat'=>$startLat,
            'startLong'=>$startLong
        );
    }

    /*
    * Вывод введенного пользователем адреса в таком виде, в котором он находится в Google Maps
    */
    public function showGeocode()
    {
        $array = $this->getGeocode();
        $startAddress = $array['startAddress'];
        echo "<div>";
        echo "Введеный Вами адрес: ".$startAddress;
        echo "</div>";
    }

    /*
    * Поиск объектов (станций метро) вокруг изначально введенного адреса
    */
    public function getLocationSearch()
    {
        $array = $this->getGeocode();
        $startPlaceId=$array['startPlaceId'];
        $startLat=$array['startLat'];
        $startLong=$array['startLong'];

        $language = $this->language;
        $apiKey = $this->apiKey;
        $type = $this->type;
        $radius=$this->radius;
        $units = $this->units;

        // Поиск станций метро в определенном радиусе (2000 метров приблизительно)
        $locationSearch=file_get_contents('https://maps.googleapis.com/maps/api/place/radarsearch/json?location='.$startLat.','.$startLong.'&radius='.$radius.'&type='.$type.'&language='.$language.'&key='.$apiKey);
        $locationSearchOutput=json_decode($locationSearch);

        if($locationSearchOutput->status == 'OK') { 
            echo "<div>";

            echo "Cтанции метро в радиусе примерно ".$radius." метров:";
            foreach ($locationSearchOutput->results as $result) {
                $endPlaceId=$result->place_id; // Получаем Place ID нашего объекта (станции метро)
                $this->getDirection($endPlaceId);
            }
            unset($result);

            echo "</div>";
        } else {
            //Поиск ближайшей станции метро в случае, если в определенном радиусе ни одной станции найдено не было
            $locationSearch=file_get_contents('https://maps.googleapis.com/maps/api/place/nearbysearch/json?location='.$startLat.','.$startLong.'&rankby=distance&type='.$type.'&language='.$language.'&key='.$apiKey);
            $locationSearchOutput=json_decode($locationSearch);

            if($locationSearchOutput->status == 'OK') {
                $endPlaceId=$locationSearchOutput->results[0]->place_id;
                $closestObject = "К сожалению, в радиусе ".$radius." метров не было найдено ни одной станции метро. Взгляните на самую ближайшую станцию: ";
                $this->getDirection($endPlaceId,$closestObject);   
            }
        }
    }

    public function getDirection($endPlaceId,$closestObject)
    {
        $array = $this->getGeocode();
        $startPlaceId=$array['startPlaceId'];

        $language = $this->language;
        $apiKey = $this->apiKey;
        $type = $this->type;
        $travelModel = $this->travelModel;
        $units = $this->units;

        $direction=file_get_contents('https://maps.googleapis.com/maps/api/directions/json?origin=place_id:'.$startPlaceId.'&destination=place_id:'.$endPlaceId.'&mode='.$travelModel.'&units='.$units.'&language='.$language.'&key='.$apiKey);
        $directionOutput=json_decode($direction);

        //Выводим информацию о времени расстоянии от исходного адреса (введенного ранее пользователем) до полученного объекта (станции метро)
        if($directionOutput->status == 'OK') { 
            $endAddress=$directionOutput->routes[0]->legs[0]->end_address;
            $distance=$directionOutput->routes[0]->legs[0]->distance->value;
            $time=$directionOutput->routes[0]->legs[0]->duration->text;
            $timeSec=$directionOutput->routes[0]->legs[0]->duration->value;
            echo $closestObject;
            echo "<div>";
            echo "<br/>";
            echo "Адрес станции метро: ".$endAddress;
            echo "<br/>";
            echo "Расстояние до станции: ".$distance." метров";
            echo "<br/>";
            echo "Время в пути до станции (пешком): ".$time." или ".$timeSec." секунд";
            echo "<br/>";
            echo "</div>";
        }
    }
}

$nearestSubwayStations = new NearestSubwayStations();

$nearestSubwayStations->showGeocode();
$nearestSubwayStations->getLocationSearch();