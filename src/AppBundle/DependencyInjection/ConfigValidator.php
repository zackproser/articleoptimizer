<?php 

namespace AppBundle\DependencyInjection; 

use Symfony\Component\Config\Definition\ConfigurationInterface; 
use Symfony\Component\Config\Definition\Builder\TreeBuilder; 

class AdvertisementConfiguration implements ConfigurationInterface
{
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder(); 
		$rootNode = $treeBuilder->root('advertisements');

		$rootNode
			->arrayNode()
				->children()
						->scalarNode('cta')->isRequired()->end()
						->scalarNode('image_url')->isRequired()->end()
						->scalarNode('url')->isRequired()->end()
					->end()
				->end()
			->end()
		;
		return $treeBuilder;
	}
}

?>