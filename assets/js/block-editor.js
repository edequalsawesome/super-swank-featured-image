( function( wp ) {
    const { __ } = wp.i18n;
    const { MediaUpload } = wp.blockEditor;
    const { Button, PanelBody } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useState, useEffect } = wp.element;
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar } = wp.editSite;

    const SuperSwankFeaturedImagePanel = () => {
        const [defaultImage, setDefaultImage] = useState(0);
        const [imageUrl, setImageUrl] = useState('');

        // Get the current default image ID from WordPress settings
        const currentDefaultImage = useSelect(select => {
            return select('core').getEntityRecord('root', 'site')?.ssfi_default_image || 0;
        }, []);

        // Update state when settings change
        useEffect(() => {
            if (currentDefaultImage !== defaultImage) {
                setDefaultImage(currentDefaultImage);
                if (currentDefaultImage) {
                    wp.media.attachment(currentDefaultImage).fetch().then(() => {
                        setImageUrl(wp.media.attachment(currentDefaultImage).get('url'));
                    });
                } else {
                    setImageUrl('');
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
            <PanelBody
                title={__('Default Featured Image', 'super-swank-featured-image')}
                initialOpen={true}
            >
                <div className="ssfi-settings-panel">
                    <div className="ssfi-image-preview">
                        {imageUrl && (
                            <img
                                src={imageUrl}
                                alt=""
                                style={{
                                    maxWidth: '100%',
                                    marginBottom: '1em',
                                    display: 'block'
                                }}
                            />
                        )}
                    </div>
                    <MediaUpload
                        onSelect={onSelectImage}
                        allowedTypes={['image']}
                        value={defaultImage}
                        render={({ open }) => (
                            <div>
                                <Button
                                    onClick={open}
                                    variant="secondary"
                                    className="editor-post-featured-image__toggle"
                                    style={{ width: '100%', marginBottom: defaultImage ? '0.5em' : 0 }}
                                >
                                    {defaultImage
                                        ? __('Replace Image', 'super-swank-featured-image')
                                        : __('Select Default Image', 'super-swank-featured-image')}
                                </Button>
                                {defaultImage > 0 && (
                                    <Button
                                        onClick={removeImage}
                                        variant="secondary"
                                        isDestructive
                                        style={{ width: '100%' }}
                                    >
                                        {__('Remove Image', 'super-swank-featured-image')}
                                    </Button>
                                )}
                            </div>
                        )}
                    />
                </div>
            </PanelBody>
        );
    };

    registerPlugin('ssfi-default-image', {
        render: () => (
            <PluginSidebar
                name="ssfi-default-image-sidebar"
                title={__('Default Featured Image', 'super-swank-featured-image')}
                icon="format-image"
            >
                <SuperSwankFeaturedImagePanel />
            </PluginSidebar>
        ),
        icon: 'format-image'
    });

} )( window.wp ); 