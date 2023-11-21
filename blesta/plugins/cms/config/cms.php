<?php
// Index page content
Configure::set('Cms.index.content', '
    <div class="col-md-4 col-sm-6 portal-box">
        <a href="{client_url}login/">
            <div class="card">
                <div class="card-body">
                    <i class="fas fa-cogs fa-4x"></i>
                    <h4>My Account</h4>
                    <p>Have an account with us? Log in here to manage your account.</p>
                </div>
            </div>
        </a>
    </div>
    {% if plugins.support_manager.enabled %}<div class="col-md-4 col-sm-6 portal-box">
        <a href="{client_url}plugin/support_manager/client_tickets/add/">
            <div class="card">
                <div class="card-body">
                    <i class="fas fa-ticket-alt fa-4x"></i>
                    <h4>Support</h4>
                    <p>Looking for help? You can open a trouble ticket here.</p>
                </div>
            </div>
        </a>
    </div>
	<div class="col-md-4 col-sm-6 portal-box">
        <a href="{client_url}plugin/support_manager/knowledgebase/">
            <div class="card">
                <div class="card-body">
                    <i class="fas fa-info-circle fa-4x"></i>
                    <h4>Knowledge Base</h4>
                    <p>Have a question? Search the knowledge base for an answer.</p>
                </div>
            </div>
        </a>
    </div>{% endif %}
    {% if plugins.order.enabled %}<div class="col-md-4 col-sm-6 portal-box">
        <a href="{blesta_url}order/">
            <div class="card">
                <div class="card-body">
                    <i class="fas fa-shopping-cart fa-4x"></i>
                    <h4>Order</h4>
                    <p>Visit the order form to sign up and purchase new products and services.</p>
                </div>
            </div>
        </a>
    </div>{% endif %}
    {% if plugins.download_manager.enabled %}<div class="col-md-4 col-sm-6 portal-box">
        <a href="{client_url}plugin/download_manager/">
            <div class="card">
                <div class="card-body">
                    <i class="fas fa-download fa-4x"></i>
                    <h4>Download</h4>
                    <p>You may need to be logged in to access certain downloads here.</p>
                </div>
            </div>
        </a>
    </div>{% endif %}'); // %1$s is the notification content from Cms.index.content_install_notice

Configure::set('Cms.index.content_install_notice', '
    <div class="col-md-12">
        <div class="thanks">
            <blockquote>
                <h4>Thank you for installing Blesta!</h4>
                <p>This is your client portal page, and you may wish to link here from your website. This message can be removed through the staff area under Settings > Plugins > Portal: Manage.</p>
                <p>
                    <ul>
                        <li>You may log into the staff area at <a href="{admin_url}">{admin_url}login/</a>.</li>
                        <li>Clients may login to the client area at <a href="{client_url}">{client_url}login/</a>.</li>
                    </ul>
                </p>
                <p>We hope you enjoy using Blesta! For help, please see the <a href="http://docs.blesta.com">documentation</a> or visit us on our <a href="http://www.blesta.com/forums/">forums</a>.</p>
            </blockquote>
        </div>
    </div>
');
