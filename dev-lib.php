<?php


$apiWsdl = 'http://shopjuniorgolf.com/api/soap/?wsdl'; 

class FormFactory{
    
    protected $_getProducts;
    protected $_client; 
    protected $_session;
    private $_envArray = array();
    private $_apiKey;
    private $_username;

    function __construct($wsdl){
            $this->getApiVariables();
            $this->_client = new SoapClient($wsdl);
            $this->_session = $this->_client->login($this->_username, $this->_apiKey);
            $this->_getProducts = $this->_client ->call ($this->_session,'datafeedwatch.products');
            $this->_client->endSession($session);
    }



    protected function getApiVariables(){

        if (file_exists('./.env')){
            
            if (trim(readline("\n.env found. Would you like to use this to provide credentials? (y/n)\n"))==="y"){

            $this->setCredentialsFromEnv();

            }
            else{
                $this->_username = trim(readline('Please Enter Username for API: '));
                $this->_apiKey = trim(readline('Please Enter API key: '));
            }

        }

        else {

            echo "\n No .env file found \n";

            $this->_username = trim(readline('Please Enter Username for API: '));
            $this->_apiKey = trim(readline('Please Enter API key: '));

        }

    }

    protected function setCredentialsFromEnv(){

        $sortedEnvArray = array();

        $dotEnv = fopen("./.env","r") or die ("Unable to open .env");
        $unformattedEnv = trim(fread($dotEnv, filesize("./.env")),"\n");
        $unsortedEnvArray = preg_split("/[:\n]+/", $unformattedEnv);
        for ($i=0;$i<sizeof($unsortedEnvArray);$i++){
            $arrayItem=array($unsortedEnvArray[$i]=>$unsortedEnvArray[$i+1]);
            $sortedEnvArray = array_merge($sortedEnvArray,$arrayItem);
            $i++;
        }   

        $this->_apiKey = $sortedEnvArray['apikey'];
        $this->_username = $sortedEnvArray['username'];

    }    

    function formatCsv(){

    $csvFile = fopen('products.csv', 'w') or die ('Unable to open products.csv');

    $csvHeader = '"SKU","Name","Price","Short_Description"'."\n";
    fwrite($csvFile, $csvHeader);

        foreach($this->_getProducts as $arrayItem){

            $productArray = array();
            //puts in array for ease of access
            array_push($productArray,$arrayItem["sku"]);
            array_push($productArray,$arrayItem["name"]);
            array_push($productArray,$arrayItem["price"]);
            array_push($productArray,$arrayItem["short_description"]);

            $csvProduct = <<<CSV
            "$productArray[0]","$productArray[1]","$productArray[2]","$productArray[3]"\n
CSV;
       
            fwrite($csvFile,$csvProduct); 
        }
    
    fclose($csvFile);
    echo "\n *CSV write complete*\n";

    }//formatCsv



    function formatJson(){

        //used to prevent "," from being put in b4 first product
        $isFirst =true;


        $jsonFile = fopen('products.json','w') or die ('Unable to open products.json');
        
        
        $jsonHeader = "{\n".'"products"'.":[";
        fwrite($jsonFile, $jsonHeader);

        foreach($this->_getProducts as $arrayItem){
            
            //adds , for inbetween products 
            if(!$isFirst){
                fwrite($jsonFile, ",\n");
            }

            $productArray = array();

            //puts products in array for easier refrence in jsonProduct            

            array_push($productArray,$arrayItem["sku"]);
            array_push($productArray,$arrayItem["name"]);
            array_push($productArray,$arrayItem["price"]);
            array_push($productArray,$arrayItem["short_description"]);

            //format for write
            $jsonProduct = <<<JSON
            {"SKU":"$productArray[0]","name":"$productArray[1]","price":"$productArray[2]","short_description":"$productArray[3]"}
JSON;
            fwrite($jsonFile, $jsonProduct);

            $isFirst = false; 

        }

        $jsonFooter = "]\n}";
        fwrite($jsonFile, $jsonFooter);
        fclose($jsonFile);
        echo "\n *JSON write complete*\n";

    }//formatJson



    function formatXml(){

        
        $xmlFile = fopen('products.xml','w') or die ('Unable to open products.xml');
        

        $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>'."\n <products>\n";
        fwrite($xmlFile, $xmlHeader);

        foreach($this->_getProducts as $arrayItem){
            $productArray = array();

            //puts in arrat for ease of access
            
            array_push($productArray,$arrayItem["sku"]);
            array_push($productArray,$arrayItem["name"]);
            array_push($productArray,$arrayItem["price"]);
            array_push($productArray,$arrayItem["short_description"]);

            $xmlProduct = <<<XML
            <product>
            <sku>"$productArray[0]"</sku>            
            <name>"$productArray[1]"</name>
            <price>"$productArray[2]"</price>
            <short_description>"$productArray[3]"</short_description>
            </product>
            \n
XML;

        fwrite($xmlFile, $xmlProduct); 

        }//foreach

        $xmlFooter = "</products>";
        fwrite($xmlFile, $xmlFooter);
        fclose($xmlFile);
        echo "\n *XML write complete*\n";
    }//formatXml
}//FormFactory


//start-up Messages
$currentDirectory = getcwd();
echo <<<WARNING
 
**WARNING**
Running this will completly overwrite any files with the following name:
\nproducts.csv
products.xml
products.json

in the directory:
$currentDirectory
\n
To continue enter one of the following commands (case-sensitive):
WARNING;
 
$startPromptMsg = <<<MSG
\n"CSV"-will output products.csv,
"XML"-will output products.xml,
"JSON"-will output products.json,
"ALL"-will output all file types,
Any other inputs will stop the script
\n
MSG;

$startPromptAnswer = trim(readline($startPromptMsg));
 
switch ($startPromptAnswer) {

    case "CSV":
            $testFactory = new FormFactory($apiWsdl);
            $testFactory -> formatCsv();
            break;

    case "XML":
            $testFactory = new FormFactory($apiWsdl);
            $testFactory -> formatXml();
            break;

    case "JSON":
            $testFactory = new FormFactory($apiWsdl);
            $testFactory -> formatJson();
            break; 

    case "ALL":
            $testFactory = new FormFactory($apiWsdl);
            $testFactory -> formatJson();
            $testFactory -> formatXml();
            $testFactory -> formatCsv();
            break;

    default:
            echo "\nGood-bye\n";
            break;
    }//switch 


echo "\n";


?>
