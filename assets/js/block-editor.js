( function( wp ) {
    const { __ } = wp.i18n;
    const { MediaUpload } = wp.mediaUtils;
    const { Button } = wp.components;
    const { dispatch } = wp.data;
    const { Fragment } = wp.element;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editSite;
    const { registerPlugin } = wp.plugins;

    const SuperSwankFeaturedImageSettings = () => {
        const defaultImage = wp.data.select('core').getEntityRecord('root', 'site').ssfi_default_image;

        const onSelectImage = (media) => {
            wp.data.dispatch('core').saveEntityRecord('root', 'site', {
                ssfi_default_image: media.id
            });
        };

        const removeImage = () => {
            wp.data.dispatch('core').saveEntityRecord('root', 'site', {
                ssfi_default_image: 0
            });
        };

        return (
            <Fragment>
                <PluginSidebarMoreMenuItem
                    target="ssfi-sidebar"
                >
                    { __('Default Featured Image', 'super-swank-featured-image') }
                </PluginSidebarMoreMenuItem>
                <PluginSidebar
                    name="ssfi-sidebar"
                    title={ __('Default Featured Image', 'super-swank-featured-image') }
                >
                    <div className="ssfi-sidebar-content">
                        <MediaUpload
                            onSelect={ onSelectImage }
                            allowedTypes={ ['image'] }
                            value={ defaultImage }
                            render={ ({ open }) => (
                                <div>
                                    { defaultImage ? (
                                        <div>
                                            <img 
                                                src={ wp.media.attachment(defaultImage).get('url') }
                                                alt=""
                                                style={{ maxWidth: '100%' }}
                                            />
                                            <Button 
                                                isDestructive
                                                onClick={ removeImage }
                                            >
                                                { __('Remove Image', 'super-swank-featured-image') }
                                            </Button>
                                        </div>
                                    ) : (
                                        <Button
                                            isPrimary
                                            onClick={ open }
                                        >
                                            { __('Select Default Image', 'super-swank-featured-image') }
                                        </Button>
                                    ) }
                                </div>
                            )}
                        />
                    </div>
                </PluginSidebar>
            </Fragment>
        );
    };

    registerPlugin( 'super-swank-featured-image', {
        render: SuperSwankFeaturedImageSettings,
        icon: 'format-image'
    } );
} )( window.wp ); 