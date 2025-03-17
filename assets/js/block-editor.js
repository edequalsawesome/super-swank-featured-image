( function( wp ) {
    const { __ } = wp.i18n;
    const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
    const { Button, Panel, PanelBody } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useState, useEffect } = wp.element;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { registerPlugin } = wp.plugins;

    const SuperSwankFeaturedImageSettings = () => {
        const [defaultImage, setDefaultImage] = useState(0);
        const [imageUrl, setImageUrl] = useState('');

        // Get the current default image ID from WordPress settings
        const currentDefaultImage = useSelect(select => {
            const settings = select('core').getEntityRecord('root', 'site');
            return settings?.ssfi_default_image || 0;
        }, []);

        // Update state when settings change
        useEffect(() => {
            if (currentDefaultImage !== defaultImage) {
                setDefaultImage(currentDefaultImage);
                if (currentDefaultImage) {
                    // Fetch the image URL
                    wp.media.attachment(currentDefaultImage).fetch().then(() => {
                        setImageUrl(wp.media.attachment(currentDefaultImage).get('url'));
                    });
                }
            }
        }, [currentDefaultImage]);

        const { saveEntityRecord } = useDispatch('core');

        const onSelectImage = (media) => {
            setDefaultImage(media.id);
            setImageUrl(media.url);
            saveEntityRecord('root', 'site', {
                ssfi_default_image: media.id
            });
        };

        const removeImage = () => {
            setDefaultImage(0);
            setImageUrl('');
            saveEntityRecord('root', 'site', {
                ssfi_default_image: 0
            });
        };

        return (
            <>
                <PluginSidebarMoreMenuItem
                    target="ssfi-sidebar"
                >
                    { __('Default Featured Image', 'super-swank-featured-image') }
                </PluginSidebarMoreMenuItem>
                <PluginSidebar
                    name="ssfi-sidebar"
                    title={ __('Default Featured Image', 'super-swank-featured-image') }
                    icon="format-image"
                >
                    <Panel>
                        <PanelBody
                            title={ __('Settings', 'super-swank-featured-image') }
                            initialOpen={ true }
                        >
                            <div className="ssfi-sidebar-content">
                                <MediaUploadCheck>
                                    <MediaUpload
                                        onSelect={ onSelectImage }
                                        allowedTypes={ ['image'] }
                                        value={ defaultImage }
                                        render={ ({ open }) => (
                                            <div style={{ marginBottom: '1em' }}>
                                                { defaultImage && imageUrl ? (
                                                    <div>
                                                        <img 
                                                            src={ imageUrl }
                                                            alt=""
                                                            style={{ 
                                                                maxWidth: '100%',
                                                                marginBottom: '1em',
                                                                display: 'block'
                                                            }}
                                                        />
                                                        <div style={{ marginBottom: '1em' }}>
                                                            <Button
                                                                onClick={ open }
                                                                variant="secondary"
                                                                style={{ marginRight: '0.5em' }}
                                                            >
                                                                { __('Replace Image', 'super-swank-featured-image') }
                                                            </Button>
                                                            <Button 
                                                                onClick={ removeImage }
                                                                variant="secondary"
                                                                isDestructive
                                                            >
                                                                { __('Remove Image', 'super-swank-featured-image') }
                                                            </Button>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <Button
                                                        onClick={ open }
                                                        variant="primary"
                                                        className="editor-post-featured-image__toggle"
                                                    >
                                                        { __('Select Default Image', 'super-swank-featured-image') }
                                                    </Button>
                                                ) }
                                            </div>
                                        )}
                                    />
                                </MediaUploadCheck>
                            </div>
                        </PanelBody>
                    </Panel>
                </PluginSidebar>
            </>
        );
    };

    registerPlugin( 'super-swank-featured-image', {
        render: SuperSwankFeaturedImageSettings,
        icon: 'format-image'
    } );
} )( window.wp ); 