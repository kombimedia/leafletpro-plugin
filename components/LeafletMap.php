<?php namespace Initbiz\LeafletPro\Components;

use Lang;
use Cms\Classes\ComponentBase;
use Initbiz\LeafletPro\Models\Marker;
use Initbiz\LeafletPro\Classes\Address;
use Initbiz\LeafletPro\Classes\AddressResolver;
use October\Rain\Exception\ApplicationException;

class LeafletMap extends ComponentBase
{
    protected $pluginPropertySuffix = 'PluginEnabled';

    public function componentDetails()
    {
        return [
            'name'        => 'initbiz.leafletpro::lang.components.leafletmap.name',
            'description' => 'initbiz.leafletpro::lang.components.leafletmap.description'
        ];
    }

    public function defineProperties()
    {
        $properties = [
            'centerLonLat' => [
                'title'             => 'initbiz.leafletpro::lang.components.leafletmap.center_lon_lat',
                'description'		=> 'initbiz.leafletpro::lang.components.leafletmap.center_lon_lat_desc',
                'type'              => 'string',
                'default'			=> '51.505, -0.09'
            ],
            'initialZoom' => [
                'title'             => 'initbiz.leafletpro::lang.components.leafletmap.zoom_title',
                'description'		=> 'initbiz.leafletpro::lang.components.leafletmap.zoom_description',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'initbiz.leafletpro::lang.components.leafletmap.zoom_validation_message',
                'default'			=> '12'
            ],
            'scrollProtection' => [
                'title'             => 'initbiz.leafletpro::lang.components.leafletmap.scroll_protection_title',
                'description'       => 'initbiz.leafletpro::lang.components.leafletmap.scroll_protection_description',
                'default'           => 'false',
                'type'              => 'checkbox',
            ],
            'getOverriding' => [
                'title'             => 'initbiz.leafletpro::lang.components.leafletmap.get_overriding_title',
                'description'       => 'initbiz.leafletpro::lang.components.leafletmap.get_overriding_description',
                'default'           => 'true',
                'type'              => 'checkbox',
            ]
        ];

        return $properties + $this->getLeafletPluginsProperties();
    }

    public function onRun()
    {
        $leafletJs = [];
        $leafletCss = [];
        $activePlugins = [];

        $leafletJs[] = 'assets/node_modules/leaflet/dist/leaflet.js';
        $leafletCss[] = 'assets/node_modules/leaflet/dist/leaflet.css';

        foreach ($this->getLeafletPlugins() as $pluginCode => $pluginDef) {
            if ($this->property($pluginCode . $this->pluginPropertySuffix)) {
                $activePlugins[] = $pluginCode;
                $leafletJs[] = $pluginDef['jsPath'];
                $leafletCss[] = $pluginDef['cssPath'];
            }
        }

        $this->addJs($leafletJs);

        $this->addCss($leafletCss);

        $this->page['activeLeafletPlugins'] = $activePlugins;
        
        $initialParams = $this->calculateInitialParams();

        $this->page['centerLonLat'] = $initialParams['centerLonLat'];
        $this->page['initialZoom'] = $initialParams['initialZoom'];

        // Leaflet use scrollWheelZoom param, to it's negated scrollProtection
        $this->page['scrollProtection'] = empty($this->property('scrollProtection')) ? 'enable' : 'disable';

        $this->page['markers'] = Marker::published()->get();
    }

    public function calculateInitialParams()
    {
        $centerLonLat = $this->property('centerLonLat');
        $initialZoom = $this->property('initialZoom');

        if ($this->property('getOverriding')) {
            $data = get();

            $address = new Address();
            $address->setFromArray($data);

            $addressResolver = new AddressResolver();

            $response = $addressResolver->resolv($this);

            $address = $response[0];

            $centerLonLat = $address['lat'] . ', ' . $address['lon'];
            $initialZomm = 1;
        }

        $result = [
            'centerLonLat' => $centerLonLat,
            'zoom' => $initialZoom,
        ];

        return $result;
    }

    /**
     * Makes properties definitions for Leaflet plugins, right now only checkboxes if enable the plugin for the component
     * @return array component properties definitions for this component
     */
    protected function getLeafletPluginsProperties()
    {
        $properties = [];

        foreach ($this->getLeafletPlugins() as $pluginCode => $pluginDef) {
            $property = [
                'title'         => $pluginDef['title'],
                'description'   => $pluginDef['description'],
                'type'          => 'checkbox',
                'group'         => 'initbiz.leafletpro::lang.components.leafletmap.plugins_group',
                'default'       => 0,
            ];

            $properties[$pluginCode . $this->pluginPropertySuffix] = $property;
        }

        return $properties;
    }

    /**
     * Registers Leaflet plugins to be used in the component
     * @return array Leaflet plugins
     */
    protected function getLeafletPlugins()
    {
        return [
            'markercluster' => [
                'title' => 'initbiz.leafletpro::lang.leafletmap_plugins.markercluster_name',
                'description' => 'initbiz.leafletpro::lang.leafletmap_plugins.markercluster_desc',
                'jsPath' => 'assets/node_modules/leaflet.markercluster/dist/leaflet.markercluster-src.js',
                'cssPath' => 'assets/node_modules/leaflet.markercluster/dist/MarkerCluster.css',
            ]
        ];
    }
}
