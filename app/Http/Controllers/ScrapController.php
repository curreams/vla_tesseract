<?php

namespace App\Http\Controllers;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;
use Laravel\Dusk\ElementResolver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

use Illuminate\Http\Request;

class ScrapController extends Controller
{

    public function test()
    {
        $links = [];
        $driver = self::getDriver();
        $browser = new Browser($driver, new ElementResolver($driver, ''));
        $url = "https://dailylists.magistratesvic.com.au/";
        $browser->visit($url);
        foreach ($browser->elements("div#CaseBrowseRegionGrid table tbody tr td a") as $a) {
            $links[] = $a->getAttribute("href");
        }
        return $links;

    }

    public function getHearingDetails($client)
    {
        $client_cases = [];
        $cases_data = [];
        $name = self::getNameFormated($client);
        $driver = self::getDriver();
        $browser = new Browser($driver, new ElementResolver($driver, ''));
        $url = "https://dailylists.magistratesvic.com.au/EFAS/CaseSearch";
        $browser->visit($url)
                ->resize(1920, 1080)
                ->maximize();
        $browser->driver->findElement( WebDriverBy::xpath("//*[@id='form0']/div[1]/label[3]"))
                ->click();
        $browser->type('CriteriaDefendantAccusedRespondent', $name);
        $browser->driver->findElement( WebDriverBy::xpath("//*[@id='form0']/div[8]/input"))
                ->click();
        $browser->waitUntilMissing('.k-loading-image', 60);
        $number_of_pages = $browser->element('span.k-state-selected')->getAttribute('innerHTML');
        if(intval($number_of_pages) > 0) {
            foreach ($browser->elements("div#CaseSearchGrid table tbody tr td a") as $a) {
                $client_cases[] = $a->getAttribute("href");
            }
            foreach ($client_cases as $key_cases => $client_case) {
                $browser->visit($client_case);
                $browser->pause(2000);
                foreach ($browser->elements("div#CaseCRIDetailMarker table tbody tr td:nth-child(2)") as $key => $td) {
                    switch ($key) {
                        case 0:
                            $cases_data[$key_cases]["case_no"] = $td->getText();
                            break;
                        case 1:
                            $cases_data[$key_cases]["hearing_date"] = $td->getText();
                            break;
                        case 3:
                            $cases_data[$key_cases]["prosecutor_agency"] = $td->getText();
                            break;
                        case 4:
                            $cases_data[$key_cases]["informant"] = $td->getText();
                            break;
                        case 5:
                            $cases_data[$key_cases]["prosecutor_representative"] = $td->getText();
                            break;
                        case 6:
                            $cases_data[$key_cases]["accused"] = $td->getText();
                            break;
                        case 7:
                            $cases_data[$key_cases]["accused_representatve"] = $td->getText();
                            break;
                        case 8:
                            $cases_data[$key_cases]["hearing_type"] = $td->getText();
                            break;
                        case 9:
                            $cases_data[$key_cases]["plea"] = $td->getText();
                            break;
                        case 10:
                            $cases_data[$key_cases]["court_room"] = $td->getText();
                            break;
                        default:
                            break;
                    }
                }
            }
            return $cases_data;
        }
    }


    private function getDriver()
    {
        $process = (new ChromeProcess)->toProcess();
        if ($process->isStarted()) {
            $process->stop();
        }
        $process->start();
        $options      = (new ChromeOptions)->addArguments(['--disable-gpu', '--headless', '--no-sandbox']);
        $capabilities = DesiredCapabilities::chrome()
        ->setCapability(ChromeOptions::CAPABILITY, $options)
        ->setPlatform('ANY');
        $driver = retry(5, function () use ($capabilities) {
            return RemoteWebDriver::create('http://localhost:9515', $capabilities, 60000, 60000);
        }, 50);
        return $driver;
    }

    private function getNameFormated($client)
    {
        $name = "";
        if(isset($client)){
            $name = $client->LastName . ", " .  $client->FirstName . " " . (!empty($client->MiddleNames)? $client->MiddleNames : "" );
        }
        return trim($name);
    }
}
