<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:fo="http://www.w3.org/1999/XSL/Format">

    <xsl:template match="/">
        <fo:root>

            <fo:layout-master-set>
                <fo:simple-page-master master-name="A4-portrait" page-height="29.7cm" page-width="21.0cm" margin="2cm">
                    <fo:region-body margin-top="2.5cm" margin-bottom="2.5cm"/>
                    <fo:region-before extent="2.0cm"/>
                    <fo:region-after extent="2.0cm"/>
                </fo:simple-page-master>
            </fo:layout-master-set>

            <fo:page-sequence master-reference="A4-portrait">
                <fo:static-content flow-name="xsl-region-before">
                    <fo:block>
                        <xsl:call-template name="header_odd"/>
                    </fo:block>
                </fo:static-content>
                <fo:static-content flow-name="xsl-region-after">
                    <fo:block>
                        <xsl:call-template name="footer_even"/>
                    </fo:block>
                </fo:static-content>
                <xsl:apply-templates select="documents" />
            </fo:page-sequence>

        </fo:root>
    </xsl:template>

    <xsl:template name="header_even">
        <fo:block font-size="8pt" border-bottom="thin solid black">
            <fo:block-container position="absolute">
                <fo:block text-align="left">
                    Last update: [date]
                </fo:block>
            </fo:block-container>
            <fo:block text-align="center">
                [Page ID]
            </fo:block>
            <fo:block-container position="absolute">
                <fo:block text-align="right">
                    [Page URL]
                </fo:block>
            </fo:block-container>
        </fo:block>
    </xsl:template>

    <xsl:template name="header_odd">
        <fo:block font-size="8pt" border-bottom="thin solid black">
            <fo:block-container position="absolute">
                <fo:block text-align="left">
                    [Date Here]
        <!--                <xsl:value-of select="date:add('1970-01-01T00:00:00Z', date:duration(/document@lastmod div 1000))" />-->
        <!--                <xsl:value-of select="/document[@lastmod]" />-->
                </fo:block>
            </fo:block-container>
            <fo:block text-align="center">
                <fo:page-number/>/<fo:page-number-citation ref-id="documents-end" />
            </fo:block>
            <fo:block-container position="absolute">
                <fo:block text-align="right">
                    <xsl:value-of select="//header[@level='1' and @pos='1']" />
                </fo:block>
            </fo:block-container>
        </fo:block>
    </xsl:template>

    <xsl:template name="footer_even">
         <fo:block font-size="8pt" border-bottom="thin solid black">
            <fo:block-container position="absolute">
                <fo:block text-align="left">
                    [Wiki URL]
                </fo:block>
            </fo:block-container>
            <fo:block-container position="absolute">
                <fo:block text-align="right">
                    Printed on [date]
                </fo:block>
            </fo:block-container>
         </fo:block>
    </xsl:template>

    <xsl:template name="footer_odd">
         <fo:block font-size="8pt" border-bottom="thin solid black">
            <fo:block-container position="absolute">
                <fo:block text-align="left">
                    @WIKI@ - @WIKIURL@
                </fo:block>
            </fo:block-container>
         </fo:block>
    </xsl:template>

    <xsl:template match="page-citation">
        <fo:block font-size="80%" border="thin solid #DDD" background-color="#EEE" padding="2mm">
            <!--
            From: <a href="@WIKIURL@">@WIKIURL@</a>&nbsp;-&nbsp;<b>@WIKI@</b>
            Permanent link:<br />
            <b><a href="@PAGEURL@">@PAGEURL@</a></b>
            Last update: <b>@UPDATE@</b>
            @QRCODE@
            -->
        </fo:block>
    </xsl:template>

    <xsl:template match="documents">
        <fo:flow flow-name="xsl-region-body">
            <xsl:apply-templates />
            <fo:block id="documents-end"></fo:block>
        </fo:flow>
    </xsl:template>

    <xsl:template match="document">
        <fo:block break-before="page">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>

    <xsl:template match="section">
        <fo:block>
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>

    <xsl:template match="header[@level='1']">
        <fo:block font-size="20pt" line-height="15pt" space-before="15pt" space-after="12pt" text-align="center">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>
    <xsl:template match="header[@level='2']">
        <fo:block font-size="17pt" line-height="15pt" space-before="15pt" space-after="12pt">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>
    <xsl:template match="header[@level='3']">
        <fo:block font-size="12pt" space-before="15pt" space-after="5pt">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>

    <xsl:template match="p">
        <fo:block font-size="10pt" line-height="15pt" space-after="12pt">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>

    <xsl:template match="strong">
        <fo:inline font-weight="bold">
            <xsl:apply-templates />
        </fo:inline>
    </xsl:template>

    <xsl:template match="monospace">
        <fo:inline font-family="monospace">
            <xsl:apply-templates />
        </fo:inline>
    </xsl:template>

    <xsl:template match="emphasis">
        <fo:inline font-style="italic">
            <xsl:apply-templates />
        </fo:inline>
    </xsl:template>

    <xsl:template match="underline">
        <fo:inline text-decoration="underline">
            <xsl:apply-templates />
        </fo:inline>
    </xsl:template>

    <xsl:template match="preformatted|code">
        <fo:block font-family="monospace" font-size="9pt" space-after="12pt"
                  white-space-collapse="false" wrap-option="no-wrap" padding="3pt"
                  linefeed-treatment="preserve" white-space-treatment="preserve">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>
    <xsl:template match="code">
        <fo:block font-family="monospace" font-size="9pt" space-after="12pt"  padding="3pt"
                  white-space-collapse="false" wrap-option="no-wrap" background-color="#efefef"
                  linefeed-treatment="preserve" white-space-treatment="preserve">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>
    <xsl:template match="file">
        <fo:block font-family="monospace" font-size="9pt" space-after="12pt"
                  white-space-collapse="false" wrap-option="no-wrap" padding="3pt"
                  linefeed-treatment="preserve" white-space-treatment="preserve" border="thin solid black">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>

    <xsl:template match="subscript">
        <fo:inline vertical-align="sub" font-size="75%">
            <xsl:apply-templates />
        </fo:inline>
    </xsl:template>

    <xsl:template match="superscript">
        <fo:inline vertical-align="super" font-size="75%">
            <xsl:apply-templates />
        </fo:inline>
    </xsl:template>

    <xsl:template match="delete">
        <fo:inline text-decoration="line-through">
            <xsl:apply-templates />
        </fo:inline>
    </xsl:template>

    <xsl:template match="linebreak">
        <fo:block></fo:block>
    </xsl:template>

    <xsl:template match="link">
        <fo:basic-link color="blue">
            <xsl:choose>
                <xsl:when test="starts-with(@href, '#')">
                    <xsl:attribute name="internal-destination">
                        <xsl:value-of select="substring(@href, 2)"/>
                    </xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="external-destination">
                        <xsl:value-of select="@href"/>
                    </xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:apply-templates />
        </fo:basic-link>
    </xsl:template>

    <xsl:template match="listu|listo">
        <fo:list-block provisional-distance-between-starts="1cm" provisional-label-separation="0.5cm">
            <xsl:attribute name="space-after">
                <xsl:choose>
                    <xsl:when test="ancestor::ul or ancestor::ol">
                        <xsl:text>0pt</xsl:text>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:text>12pt</xsl:text>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>
            <xsl:attribute name="start-indent">
                <xsl:variable name="ancestors">
                    <xsl:choose>
                        <xsl:when test="count(ancestor::ol) or count(ancestor::ul)">
                            <xsl:value-of select="1 + 
                                    (count(ancestor::ol) + 
                                     count(ancestor::ul)) * 
                                    1.25"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:text>1</xsl:text>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>
                <xsl:value-of select="concat($ancestors, 'cm')"/>
            </xsl:attribute>
            <xsl:apply-templates />
        </fo:list-block>
    </xsl:template>

    <xsl:template match="listitem">
        <fo:list-item>
            <fo:list-item-label end-indent="label-end()">
                <fo:block>&#x2022;</fo:block>
            </fo:list-item-label>
            <fo:list-item-body start-indent="body-start()">
                <fo:block>
                    <xsl:apply-templates />
                </fo:block>
            </fo:list-item-body>
        </fo:list-item>
    </xsl:template>

    <xsl:template match="listcontent">
        <fo:block font-size="10pt" line-height="15pt">
            <xsl:apply-templates />
        </fo:block>
    </xsl:template>

</xsl:stylesheet>
