<?php

/******************************************************************************* 
  Copyright(c) 2015 CodeLathe LLC. All rights Reserved.
 *******************************************************************************/
/**
 * API Class for FileCloud
 * 
 * Create a CloudAPI object for User Level API commands
 * Create a CloudAdminAPI object for Admin Level API commands
 * 
 * Calling APIs will either return a collection object which contains different records
 * or an individual record object where only one record is returned.
 * 
 * Collection Objects can contain a meta record object that contains general information
 * about the records returned. They also contain a number of data record objects.
 *
 *  [Collection]
 *    |
 *    +---------------[Meta Record]
 *    |
 *    +---------------[1 ..n Data Records]
 * 
 * Depending upon the API, you might get different types of Data Records Back
 * Refer to the API documentation to understand which record type is being returned
 * 
 *  [DataRecord]
 *    |
 *    +----------- [CommandRecord]
 *    +----------- [FolderPropertiesRecord]
 *    +----------- [AuthenticationRecord]
 *    +----------- [ShareRecord]
 *    +----------- [CommentRecord]
 *    +----------- [UserRecord]
 *    +----------- [ProfileRecord]
 *    +----------- [LangRecord]
 *    +----------- and so on...
 *  
 * Usage:
 * /////////////////////////////////////////////////////////////////////////////
 * Create a Cloud API object to work with User level APIs
 * $cloudAPI = new CloudAPI("http://myfilecloudserver.com");
 *
 * * // ... Login the User
 * $record = $cloudAPI->loginGuest("john", "password"); 
 * 
 * // ... Check if the result is OK
 * if ($record->getResult() == '1')
 *  echo "Logged in OK";
 * 
 * // ... Create a new folder
 * $record = $cloudAPI->createFolder('/john', $folder);
 *
 * if ($record->getResult() == '1')
 *  echo "Created a new folder OK";
 * /////////////////////////////////////////////////////////////////////////////
 * Create a Cloud Admin API object to work with User level APIs
 * $cloudAdminAPI = new CloudAdminAPI("http://myfilecloudserver.com");
 *
 * * // ... Login the Admin
 * $record = $cloudAdminAPI->adminLogin("admin", "password"); 
 * 
 * // ... Check if the result is OK
 * if ($record->getResult() == '1')
 *  echo "Logged in Admin OK";
 * ///////////////////////////////////////////////////////////////////////////// 
 * 
 * @since   Oct 10, 2015 — Last update Oct 10, 2015
 * @link    http://www.getfilecloud.com
 * @version 1.0
 */


class Collection {

    private $m_records;
    private $m_recordName;
    private $m_meta;
    private $m_buffer;
    private $m_success = false;

    public function __construct($buffer, $recordName, $recordType = "DataRecord", $meta = "") {
        $this->m_records = array();
        $this->m_recordName = $recordName;
        $this->m_buffer = $buffer;
        $this->m_meta = $meta;
        
        try {
            $xml = new SimpleXMLElement($buffer);
            
            foreach ($xml as $record) {
                $name = $record->getName();

                $array = array();
                foreach ($record as $k => $v) {
                    $array[$k] = (string) $v;
                }

                if ($name == $recordName) {
                    $this->m_records[] = new $recordType($array);
                } else if ($meta != "" && $name == $meta) {
                    $this->m_meta = new DataRecord($array);
                }
            }
            $this->m_success = true;
        } catch (Exception $e) {
            $btrace = $e->getTraceAsString();
            echo 'Caught exception: ', $e->getMessage(), "\n";
            echo 'Trace: ', $btrace, "\n";
            echo $buffer;
            echo "\n";
            die();
        }

        //Kill the script right here if the expected data is not seen
        if ($this->m_success != true) {
            echo "ABORT: Unable to parse response!\nRESPONSE BUFFER\n";
            echo $buffer;
            echo "\n";
            die();
        }
        /*    
        if (count($this->m_records) == 0) {
            echo "WARNING: No record of type (" . $recordName . ") found!\nRESPONSE BUFFER\n";
            echo $buffer;
            echo "\n";
        }
        */
    }

    public function getNumberOfRecords() {
        if ($this->m_success == true) {
            return count($this->m_records);
        }
        return 0;
    }

    public function getRecords() {
        return $this->m_records;
    }

    public function getMetaRecord() {
        return $this->m_meta;
    }
}

//------------------------------------------------------------------------------
class DataRecord
{
    protected $m_record;

    public function __construct($record) {
        $this->m_record = $record;
    }

    public function getValueforKey($key) {
        if (isset($this->m_record[$key])) {
            return $this->m_record[$key];
        }
        return false;
    }

    public function getRecord() {
        return $this->m_record;
    }
    
    public function getObjectName()
    {
        return get_class();
    }
}

//------------------------------------------------------------------------------
class CommandRecord extends DataRecord
{
    public function getType() {
        return $this->m_record['type'];
    }

    public function getResult() {
        return $this->m_record['result'];
    }

    public function getMessage() {
        return $this->m_record['message'];
    }
}
//------------------------------------------------------------------------------
class FolderPropertiesRecord extends DataRecord
{
    public function getTotalFolder() {
        return $this->m_record['totalfolders'];
    }

    public function getTotalFiles() {
        return $this->m_record['totalfiles'];
    }

    public function getTotalSize() {
        return $this->m_record['totalsize'];
    }

    public function getVersionedFiles() {
        return $this->m_record['versionedfiles'];
    }

    public function getVersionedSize() {
        return $this->m_record['versionedsize'];
    }

    public function getLiveFiles() {
        return $this->m_record['livefiles'];
    }

    public function getLiveFolders() {
        return $this->m_record['livefolders'];
    }

    public function getLiveSize() {
        return $this->m_record['livesize'];
    }
}

// -----------------------------------------------------------------------------
class AuthenticationRecord extends DataRecord
{
	
    public function getProfile() {
        return $this->m_record['profile'];
    }

    public function getDisplayName() {
        return $this->m_record['displayname'];
    }

    public function getPeerID() {
        return $this->m_record['peerid'];
    }

    public function getAuthenticated() {
        return $this->m_record['authenticated'];
    }

    public function getOS() {
        return $this->m_record['OS'];
    }

    public function getAuthType() {
        return $this->m_record['authtype'];
    }
}

//------------------------------------------------------------------------------
class ShareRecord extends DataRecord
{
    public function getShareId()
    {
        return $this->m_record['shareid'];
    }
    
    public function getShareName()
    {
        return $this->m_record['sharename'];
    }
    
    public function getShareLocation()
    {
        return $this->m_record['sharelocation'];
    }
    
    public function getShareOwner()
    {
        return $this->m_record['shareowner'];
    }
    
    public function getShareUrl() {
        return $this->m_record['shareurl'];
    }

    public function getViewmode() {
        return $this->m_record['viewmode'];
    }

    public function getValidityPeriod() {
        return $this->m_record['validityperiod'];
    }

    public function getSharesizeLimit() {
        return $this->m_record['sharesizelimit'];
    }

    public function getMaxdownloads() {
        return $this->m_record['maxdownloads'];
    }

    public function getDownloadCount() {
        return $this->m_record['downloadcount'];
    }

    public function getViewsize() {
        return $this->m_record['viewsize'];
    }

    public function getThumbsize() {
        return $this->m_record['thumbsize'];
    }

    public function getAllowPublicAccess() {
        return $this->m_record['allowpublicaccess'];
    }

    public function getAllowPublicUpload() {
        return $this->m_record['allowpublicupload'];
    }

    public function getAllowPublicViewonly() {
        return $this->m_record['allowpublicviewonly'];
    }

    public function getIsdir() {
        return $this->m_record['isdir'];
    }

    public function getIsvalid() {
        return $this->m_record['isvalid'];
    }

    public function getCreateddDate() {
        return $this->m_record['createddate'];
    }

    public function getAllowEdit() {
        return $this->m_record['allowedit'];
    }

    public function getAllowDelete() {
        return $this->m_record['allowdelete'];
    }

    public function getAllowSync() {
        return $this->m_record['allowsync'];
    }

    public function getAllowShare() {
        return $this->m_record['allowshare'];
    }

}
//------------------------------------------------------------------------------
class CommentRecord extends DataRecord
{
    public function getId() {
        return $this->m_record['id'];
    }

    public function getwho() {
        return $this->m_record['who'];
    }

    public function getwhen() {
        return $this->m_record['when'];
    }

    public function gettext() {
        return $this->m_record['text'];
    }

}

//------------------------------------------------------------------------------
class UserRecord extends DataRecord
{
    public function getUserName() {
        return $this->m_record['username'];
    }
    public function getEmail() {
        return $this->m_record['email'];
    }
    public function getDisplayName() {
        return $this->m_record['displayname'];
    }
    public function getCreated() {
        return $this->m_record['created'];
    }
    public function getStatus() {
        return $this->m_record['status'];
    }
    public function getTotal(){
        return $this->m_record['total'];
    }
    public function getSizeingb(){
        return $this->m_record['sizeingb'];
    }
    public function getSize(){
        return $this->m_record['size'];
    }
    public function getVerfied(){
        return $this->m_record['verified'];
    }
    public function getAdminstatus(){
        return $this->m_record['adminstatus'];
    }
    public function getSharemode(){
        return $this->m_record['sharemode'];
    }
    public function getDisablemyfilessync(){
        return $this->m_record['disablemyfilessync'];
    }
    public function getDisablenetworksync(){
        return $this->m_record['disablenetworksync'];
    }
    public function getlastlogindate(){
        return $this->m_record['lastlogindate'];
    }
    public function getAuthtype(){
        return $this->m_record['authtype'];
    }
    public function getExpirationdate(){
        return $this->m_record['expirationdate'];
    }
    public function getSizeusedwithshares(){
        return $this->m_record['sizeusedwithshares'];
    }
    public function getSizeusedwithoutshares(){
        return $this->m_record['sizeusedwithoutshares'];
    }
    public function getFreespace(){
        return $this->m_record['freespace'];
    }
     
}

//------------------------------------------------------------------------------
class ProfileRecord extends DataRecord
{
    public function getNickName() {
        if (isset($this->m_record['nickname']))
            return $this->m_record['nickname'];
        return '';
    }

    public function getPeerID() {
        if (isset($this->m_record['peerid']))
            return $this->m_record['peerid'];
        return '';
    }

    public function getProfileRoot() {
        if (isset($this->m_record['profileroot']))
            return $this->m_record['profileroot'];
        return '';
    }

    public function getLocation() {
        return $this->m_record['location'];
    }

    public function getDisplayName() {
        return $this->m_record['displayname'];
    }

    public function getEmail() {
        if (isset($this->m_record['profileroot']))
            return $this->m_record['email'];

        return '';
    }

    public function getSecretQn() {
        return $this->m_record['secretqn'];
    }

    public function getSecretAns() {
        return $this->m_record['secretans'];
    }

    public function getHint() {
        return $this->m_record['hint'];
    }

    public function getDateForamt() {
        return $this->m_record['dateformat'];
    }

    public function getIsRemote() {
        return $this->m_record['isremote'];
    }

    public function getProfileUserDataDir() {
        return $this->m_record['profileuserdatadir'];
    }

    public function getEmailVerifyTag() {
        return $this->m_record['emailverifytag'];
    }

}

//------------------------------------------------------------------------------
class LangRecord extends DataRecord
{
    public function getLangName() {
        return $this->m_record['name'];
    }

    public function getCurrent() {
        return $this->m_record['current'];
    }

}

//------------------------------------------------------------------------------
class GroupRecord extends DataRecord
{
    public function getGroupId() {
        return $this->m_record['groupid'];
    }

    public function getGroupName() {
        return $this->m_record['groupname'];
    }  
    
    public function getEveryoneGroup() {
        return $this->m_record['everyonegroup'];    
    }
    
    public function getCreatedOn() {
        return $this->m_record['createdon'];    
    }
    
    public function getEmailId() {
        return $this->m_record['emailid'];    
    }
    
    public function getAutosynGroup() {
        return $this->m_record['autosyncgroup'];    
    }
}
//------------------------------------------------------------------------------
class EncryptionstatusRecord extends DataRecord
{
    public function getStatuscode() {
        return $this->m_record['statuscode'];
    }
    public function getStatusmsg() {
        return $this->m_record['statusmsg'];
    }
    public function getRecoverykeyactive() {
        return $this->m_record['recoverykeyactive'];
    }
    public function getRecoverykeynotdownloaded() {
        return $this->m_record['recoverykeynotdownloaded'];
    }
}
	
class ExternalRecord extends DataRecord
{
    public function getExternalid() {
        return $this->m_record['externalid'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    public function getLocation() {
        return $this->m_record['location'];
    }
    public function getNumberofUsers() {
        return $this->m_record['numusers'];
    }
    public function getNumberofGroups() {
        return $this->m_record['numgroups'];
    }
}

// FavoriteRecord
class FavoriteRecord extends DataRecord
{
    public function getId() {
        return $this->m_record['id'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    public function getParentId() {
        return $this->m_record['parentid'];
    }
    public function getType() {
        return $this->m_record['type'];
    }
    public function getCount() {
        return $this->m_record['count'];
    }
}

// PermissionRecord
class PermissionRecord extends DataRecord
{
    public function getRead() {
        return $this->m_record['read'];
    }
    public function getWrite() {
        return $this->m_record['write'];
    }
    public function getShare() {
        return $this->m_record['share'];
    }
    public function getSync() {
        return $this->m_record['sync'];
    }
    public function getCreate() {
        return $this->m_record['create'];
    }
    public function getUpdate() {
        return $this->m_record['update'];
    }
    public function getDelete() {
        return $this->m_record['delete'];
    }
    public function getIsSharedToYou() {
        return $this->m_record['issharedtoyou'];
    }
    public function getShareOwner(){
        return $this->m_record['shareowner'];
    }
}

// License Record
class LicenseRecord extends DataRecord
{
    public function getAccounts() {
        return $this->m_record['accounts'];
    }
    public function getUsedAccounts() {
        return $this->m_record['usedaccounts'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    
}

// LanguageRecord
class LanguageRecord extends DataRecord
{
    public function getName() {
        return $this->m_record['name'];
    }
    public function getCurrent() {
        return $this->m_record['current'];
    }
}

// VersionRecord
class VersionRecord extends DataRecord
{
    public function getVersionNumber() {
        return $this->m_record['versionnumber'];
    }
    public function getSize() {
        return $this->m_record['size'];
    }
    public function getCreatedOn() {
        return $this->m_record['createdon'];
    }
    public function getCreatedBy() {
        return $this->m_record['createdby'];
    }
    public function getFileName() {
        return $this->m_record['filename'];
    }
    public function getSizeInBytes() {
        return $this->m_record['sizeinbytes'];
    }
    public function getFileId() {
        return $this->m_record['fileid'];
    }
}

class ConfigSettingRecord extends DataRecord
{
    public function getParam() {
        return $this->m_record['param'];
    }
    public function getValue() {
        return $this->m_record['value'];
    }
    public function getIsValid(){
        return $this->m_record['isvalid'];
    }
}

// EntryRecord
class EntryRecord extends DataRecord
{
    public function getPath() {
        return $this->m_record['path'];
    }
    public function getDirPath() {
        return $this->m_record['dirpath'];
    }
    public function getFileName() {
        return $this->m_record['name'];
    }
    public function getFileExt() {
        return $this->m_record['ext'];
    }
    public function getIsRoot() {
        return $this->m_record['isroot'];
    }
    public function getIsShareable() {
        return $this->m_record['isshareable'];
    }
    public function getCanFavorite() {
        return $this->m_record['canfavorite'];
    }
    public function getFullFileName() {
        return $this->m_record['fullfilename'];
    }
    public function getSize() {
        return $this->m_record['size'];
    }
    public function getFullSize() {
        return $this->m_record['fullsize'];
    }
    public function getType() {
        return $this->m_record['type'];
    }
    public function getFavoriteId() {
        return $this->m_record['favoriteid'];
    }
    public function getModified() {
        return $this->m_record['modified'];
    }
    public function getModifiedEpoch() {
        return $this->m_record['modifiedepoch'];
    }
     public function getFavoriteListId() {
        return $this->m_record['favoritelistid'];
    }
}

//UserUsageRecord

class UserUsageRecord extends DataRecord
{
    public function getUserName() {
        return $this->m_record['username'];
    }
    public function getSizeUsedWithShares() {
        return $this->m_record['sizeusedwithshares'];
    }
    public function getSizeUsedWithoutShares() {
        return $this->m_record['sizeusedwithoutshares'];
    }
    public function getFreeSpace() {
        return $this->m_record['freespace'];
    }
   
}

// UsageRecord
class UsageRecord extends DataRecord
{
    public function getStorageUsage() {
        return $this->m_record['storageusage'];
    }
    public function getSizeLimit() {
        return $this->m_record['sizelimit'];
    }
    public function getUsagePercent() {
        return $this->m_record['usagepercent'];
    }
    public function getTotalFiles() {
        return $this->m_record['totalfiles'];
    }
    public function getTotalFolders() {
        return $this->m_record['totalfolders'];
    }
    public function getTotalSize() {
        return $this->m_record['totalsize'];
    }
    public function getVersionedSize() {
        return $this->m_record['versionedsize'];
    }
    public function getVersionedFiles() {
        return $this->m_record['versionedfiles'];
    }
    public function getLiveFiles() {
        return $this->m_record['livefiles'];
    }
    public function getLiveFolders() {
        return $this->m_record['livefolders'];
    }
    public function getLiveSize() {
        return $this->m_record['livesize'];
    }
    public function getRecycleFolders() {
        return $this->m_record['recyclefolders'];
    }
    public function getRecycleFiles() {
        return $this->m_record['recyclefiles'];
    }
    public function getRecycleSize() {
        return $this->m_record['recyclesize'];
    }
}

// ActivityRecord
class ActivityRecord extends DataRecord
{
    public function getPath() {
        return $this->m_record['path'];
    }
    public function getIsFile() {
        return $this->m_record['isfile'];
    }
    public function getParent() {
        return $this->m_record['parent'];
    }
    public function getActionCode() {
        return $this->m_record['actioncode'];
    }
    public function getWho() {
        return $this->m_record['who'];
    }
    public function getWhen() {
        return $this->m_record['when'];
    }
    public function getHow() {
        return $this->m_record['how'];
    }
}

//------------------------------------------------------------------------------
// LockRecord
class LockRecord extends DataRecord
{
    public function getLockrId() {
        return $this->m_record['lockrid'];
    }
    public function getLockuserId() {
        return $this->m_record['lockuserid'];
    }
    public function getLockPath() {
        return $this->m_record['lockpath'];
    }
    public function getLockExpiration() {
        return $this->m_record['lockexpiration'];
    }
    public function getLockReadlock() {
        return $this->m_record['lockreadlock'];
    }
}
//------------------------------------------------------------------------------
// System Status Record
class StatusRecord extends DataRecord
{
    public function getApiLevel() {
        return $this->m_record['apilevel'];
    }
    public function getPeerId() {
        return $this->m_record['peerid'];
    }
    public function getDisplayName() {
        return $this->m_record['displayname'];
    }
    public function getUserStatus() {
        return $this->m_record['userstatus'];
    }
    public function getOs() {
        return $this->m_record['OS'];
    }
    public function getCurrentProfile() {
        return $this->m_record['currentprofile'];
    }
    public function getHttpPort() {
        return $this->m_record['httpport'];
    }
    public function getRelayActive() {
        return $this->m_record['relayactive'];
    }
    public function getServerUrl() {
        return $this->m_record['serverurl'];
    }
    public function getAuthType() {
        return $this->m_record['authtype'];
    }
    public function getMediaSyncStorePath() {
        return $this->m_record['mediasyncstorepath'];
    }
    public function getPasswordMinLength() {
        return $this->m_record['passwordminlength'];
    }
    public function getEmail() {
        return $this->m_record['email'];
    }    
}

//------------------------------------------------------------------------
class UserListRecord extends DataRecord
{
    public function getUserName()
    {
        return $this->m_record['name'];
    }
    public function getWriteMode()
    {
        return $this->m_record['writemode'];
    }
}

//------------------------------------------------------------------------
class GroupListRecord extends DataRecord
{
    public function getGroupName()
    {
        return $this->m_record['groupname'];
    }
    public function getGroupId()
    {
        return $this->m_record['id'];
    }
    public function getWriteMode()
    {
        return $this->m_record['writemode'];
    }
}
//------------------------------------------------------------------------
// Members Record
class MembersRecord extends DataRecord
{
    public function getName() {
        return $this->m_record['name'];
    }
}



//------------------------------------------------------------------------------
// AD Group Record
class AdgroupRecord extends DataRecord
{
    public function GetEntry() {
        return $this->m_record['group'];
    }
}

//------------------------------------------------------------------------------
// AD Group Member Record
class AdgroupMemberRecord extends DataRecord
{
     public function getMembers() {
        return $this->m_record['member'];
    } 
}  
//------------------------------------------------------------------------------
class AdminUsersRecord extends DataRecord
{
    public function getAdminUserName()
    {
        return $this->m_record['name'];
    }
}

//------------------------------------------------------------------------------
class UserOperationsRecord extends DataRecord
{
    public function getOpName()
    {
        return $this->m_record['opname'];
    }
    public function getUpdate()
    {
        return $this->m_record['update'];
    }
}

//------------------------------------------------------------------------------
class AlertsRecord extends DataRecord
{
    public function getRid()
    {
        return $this->m_record['rid'];
    }
    public function getLevel()
    {
        return $this->m_record['level'];
    }
    public function getType()
    {
        return $this->m_record['type'];
    }
    public function getDescription()
    {
        return $this->m_record['description'];
    }
}

//------------------------------------------------------------------------------
class ItemRecord extends DataRecord
{
    public function getWho()
    {
        return $this->m_record['who'];
    }
    public function getFileName()
    {
        return $this->m_record['name'];
    }
    public function getSize()
    {
        return $this->m_record['size'];
    }
    public function getCreated()
    {
        return $this->m_record['created'];
    }
    public function getHow()
    {
        return $this->m_record['how'];
    }
}
class DoNotEmailRecord extends DataRecord
{
    public function getRid()
    {
        return $this->m_record['rid'];
    }
    public function getEmail()
    {
        return $this->m_record['email'];
    }
}

class SiteRecord extends DataRecord
{
    public function getSiteId()
    {
        return $this->m_record['siteid'];
    }
    public function getSiteName()
    {
        return $this->m_record['name'];
    }
    public function getSiteUrl()
    {
        return $this->m_record['host'];
    }
    public function getSiteAllocatedQuota()
    {
        return $this->m_record['allocatedquota'];
    }
    public function getSiteTotalUsers()
    {
        return $this->m_record['totalusers'];
    }
    public function getSiteCurrentUsers()
    {
        return $this->m_record['currentusers'];
    }
    public function getSiteUserQuota()
    {
        return $this->m_record['usedquota'];
    }
}

class AuditRecord extends DataRecord
{
    public function getId()
    {
        return $this->m_record['id'];
    }
    public function getUserName()
    {
        return $this->m_record['username'];
    }
    public function getMessage()
    {
        return $this->m_record['message'];
    }
    public function getIP()
    {
        return $this->m_record['ip'];
    }
    public function getCreatedOn()
    {
        return $this->m_record['createdon'];
    }
    public function getAgent()
    {
        return $this->m_record['agent'];
    }
}


// -----------------------------------------------------------------------
// FILECLOUD API CLASS
// -----------------------------------------------------------------------
class APICore {

    public $curl_handle;
    public $server_url;
    public $start_time;
    public $end_time;
    public $user_agent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36';

    public function __construct($SERVER_URL) {
        $this->init($SERVER_URL);
    }

    public function init($SERVER_URL) {
        $this->server_url = $SERVER_URL;
        $this->curl_handle = curl_init();
        curl_setopt($this->curl_handle, CURLOPT_COOKIEJAR, dirname(__FILE__) . DIRECTORY_SEPARATOR . "cookie.txt");
        curl_setopt($this->curl_handle, CURLOPT_COOKIEFILE, dirname(__FILE__) . DIRECTORY_SEPARATOR . "cookie.txt");
        curl_setopt($this->curl_handle, CURLOPT_TIMEOUT, 1200);
        curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl_handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->curl_handle, CURLOPT_MAXREDIRS, 4);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 0);
        curl_setopt($this->curl_handle, CURLOPT_USERAGENT, $this->user_agent);
    }
    
    protected function startTimer()
    {
        $this->start_time = microtime(true);
        $this->end_time = $this->start_time;
    }
    
    protected function stopTimer()
    {
        $this->end_time = microtime(true);
    }

    public function elapsed()
    {
        return round(abs($this->end_time - $this->start_time),3);
    }
    
    public function __destruct() {
        curl_close($this->curl_handle);
        if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . "cookie.txt")) {
            unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . "cookie.txt");
        }
    }

    protected function doGET($url) {
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 0);
        curl_setopt($this->curl_handle, CURLOPT_HTTPGET, 1);
        return curl_exec($this->curl_handle);
    }

    protected function doPOST($url, $postdata) {
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_HTTPGET, 0);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $postdata);
        return curl_exec($this->curl_handle);
    }
    
    protected function doPOSTWithAgent($url, $postdata )
    {
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_HTTPGET, 0);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        return curl_exec($this->curl_handle);	
    }    

    protected function getCurlValue($filename) {
        // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) { 
            return curl_file_create(realpath($filename), '', $filename);
        }   
        
        // Use the old style if using an older version of PHP
        $value = '@' . realpath($filename);
        
        return $value;
    }

    protected function doUpload($url, $filename) {
        $cfile = $this->getCurlValue($filename);
		 $post = array('file_contents' => $cfile);
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $post);
        return curl_exec($this->curl_handle);
       
    }
    
     protected function getCurlValueForChunked($tempfile ,$filename) {
        // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) { 
            return curl_file_create($tempfile, '', $filename);
        }   
        
        // Use the old style if using an older version of PHP
        $value = '@' . $tempfile. ';filename='.$filename;
        
        return $value;
    }
    
    protected function doChunkedUpload($url, $filechunkpath, $filename) {
        $cfile = $this->getCurlValueForChunked($filechunkpath,$filename);
        $post = array('file_contents' => $cfile);
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $post);
        return curl_exec($this->curl_handle);
    }
    
    }

class CloudAPI extends APICore {
    
    public function __construct($SERVER_URL) {
        parent::__construct($SERVER_URL);
    }

    public function __destruct() {
        parent::__destruct();
    }
    
    // ---- GET AUTHENTICATION INFO API
    // RETURNS a AuthenticationInfo Record
    public function getAuthenticationInfo() {
        $this->startTimer();
        $url = $this->server_url . "/core/getauthenticationinfo";
        $buffer = $this->doGET($url);
        $collection = new Collection($buffer, "info", "AuthenticationRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
          
        
    }

    // ---- LOGIN GUEST API
    // USERNAME: takes a username or email address
    // PASSWORD: takes the password for the specified user
    // RETURNS a CommandRecord
    public function loginGuest($user, $password) {
        $this->startTimer();
        $url = $this->server_url . "/core/loginguest";
        $postdata = 'password=' . $password . '&userid=' . $user;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }
    
    public function loginGuestWithAgent($user, $password)
    {
	$url = $this->server_url . "/core/loginguest";
	$postdata = 'password=' . $password . '&userid=' . $user;
	$buffer = $this->doPOSTWithAgent($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }
    // ---- CREATEFOLDER API
    // RETURNS a CommandRecord
    public function createFolder($path, $name) {
        $this->startTimer();
        $url = $this->server_url . "/core/createfolder";
        $postdata = 'name=' . $name . '&path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    // ---- DELETEFILE API
    // RETURNS a CommandRecord
    public function deleteFile($path, $name) {
        $this->startTimer();
        $url = $this->server_url . "/core/deletefile";
        $postdata = 'name=' . $name . '&path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //--- 2FA Login API
    //RETURNS a CommandRecord
    public function twofalogin($userid, $code, $token) {
        $this->startTimer();
        $url = $this->server_url . "/core/2falogin";
        $postdata = 'userid=' . $userid . '&code=' . $code . '&token=' . $token;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---- Logout API
    //RETURNS a CommandRecord
    public function lockSession() {
        $this->startTimer();
        $url = $this->server_url . "/core/locksession";
        $postdata = "";
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---- Resend 2FA code API
    //RETURNS a CommandRecord
    public function resend2facode($userid, $token) {
        $this->startTimer();
        $url = $this->server_url . "/core/resend2facode";
        $postdata = 'userid=' . $userid . '&token=' . $token;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---- Lock API
    //RETURNS a CommandRecord
    public function lock($path, $expiration = "", $readlock = "") {
        $this->startTimer();
        $url = $this->server_url . "/core/lock";

        if ($expiration != "" && $expiration != null) {
            $expiration = '&expiration=' . $expiration;
        }
        if ($readlock != "" && $readlock != null) {
            $readlock = '&readlock=' . $readlock;
        }
        $postdata = 'path=' . $path . $expiration . $readlock;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---- Unlock API
    //RETURNS a CommandRecord
    public function unLock($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/unlock";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---- SHOWHIDEACTIVITY API
    //RETURNS a CommanRecord
    public function showhideActivity($collapse) {
        $this->startTimer();
        $url = $this->server_url . "/core/showhideactivity";
        $postdata = 'collapse=' . $collapse;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---- ADDCOMMENT API
    //RETURNS a CommentRecord for sucess and command record for failure
    public function addComment($fullpath, $parent, $isfile, $text) {
        $this->startTimer();
        $url = $this->server_url . "/core/addcommentforitem";
        $postdata = 'fullpath=' . $fullpath . '&parent=' . $parent . '&isfile=' . $isfile . '&text=' . $text;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "comment", "CommentRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        $this->stopTimer();
        return NULL;
    }

    //---- REMOVECOMMENT API
    //RETURNS a CommandRecord
    public function removeComment($fullpath, $id) {
        $this->startTimer();
        $url = $this->server_url . "/core/removecommentforitem";
        $postdata = 'fullpath=' . $fullpath . '&id=' . $id;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        $this->stopTimer();
        return NULL;
    }
    
    //---Upload API
    //Returns a Record
    public function upload($appname, $path, $filename, $complete=1) {
        $this->startTimer();
        $url = $this->server_url . '/core/upload?appname=' . $appname . '&path=' . $path . '&offset=0&complete='.$complete.'&filename=' . $filename;
        $buffer = $this->doUpload($url, $filename);
       
		
        $this->stopTimer();
        return $buffer;
    }
    
     public function chunkedUpload($appname, $filename, $tmpfile, $cloudpath,$offset, $complete=1) {
        $this->startTimer();
        $url = $this->server_url . '/core/upload?appname=' . $appname . '&path=' . $cloudpath . '&offset='.$offset.'&complete='.$complete.'&filename=' . $filename;
        echo $url;
        $buffer = $this->doChunkedUpload($url, $tmpfile, $filename);
        $this->stopTimer();
        var_dump($buffer);
        return $buffer;
    }

    public function removeFavoriteList($id) {
        $this->startTimer();
        $url = $this->server_url . "/core/removefavoritelist";
        $postdata = 'id=' . $id;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function renameFile($path, $name, $newname) {
        $this->startTimer();
        $url = $this->server_url . "/core/renamefile";
        $postdata = 'path=' . $path . '&name=' . $name . '&newname=' . $newname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function copyFile($path, $name, $copyto) {
        $this->startTimer();
        $url = $this->server_url . "/core/copyfile";
        $postdata = 'path=' . $path . '&name=' . $name . '&copyto=' . $copyto;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function moveFile($fromname, $toname) {
        $this->startTimer();
        $url = $this->server_url . "/core/renameormove";
        $postdata = 'fromname=' . $fromname . '&toname=' . $toname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function rotateFsImage($name, $angle) {
        $this->startTimer();
        $url = $this->server_url . "/core/rotatefsimage";
        $postdata = 'name=' . $name . '&angle=' . $angle;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function addUsertoShare($userid, $shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/addusertoshare";
        $postdata = 'userid=' . $userid . '&shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function setAllowpublicAccess($shareid, $allowpublicaccess, $allowpublicupload, $allowpublicviewonly) {
        $this->startTimer();
        $url = $this->server_url . "/core/setallowpublicaccess";
        $postdata = 'shareid=' . $shareid . '&allowpublicaccess=' . $allowpublicaccess .
                '&allowpublicupload=' . $allowpublicupload . '&allowpublicviewonly=' . $allowpublicviewonly;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function getShareForPath($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/getshareforpath";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "share", "ShareRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        $this->stopTimer();
        return NULL;
    }
    public function getShareForID($shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/getshareforid";
        $postdata = 'shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "share", "ShareRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }    
//        public function addshare($sharelocation)
//        {
//            $url = $this->server_url . "/core/addshare";
//            $postdata = 'sharelocation=' . $sharelocation;
//            $buffer = $this->doPOST($url, $postdata);
//            return new CommandRecord($buffer);
//        }

    public function deleteshare($shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/deleteshare";
        $postdata = 'shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function deleteUserFromShare($userid, $shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/deleteuserfromshare";
        $postdata = 'userid=' . $userid . '&shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function createprofile($profilename, $emailid, $password) {
        $this->startTimer();
        $url = $this->server_url . "/core/createprofile";
        $postdata = 'profile=' . $profilename . '&email=' . $emailid . '&password=' . $password;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function logout() {
        $this->startTimer();
        $url = $this->server_url . "/admin/?op=logout";
        $postdata = "";
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function getfolderproperties($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/getfolderproperties";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $xml = new SimpleXMLElement($buffer);
        $collection = new Collection($buffer, "usage", "FolderPropertiesRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
        	$arr= $collection->getRecords();
			return $arr[0];
        }

        $this->stopTimer();
        return NULL;
    }

    public function unsetfavorite($favoritelistid, $path) {
        $this->startTimer();
        $url = $this->server_url . "/core/unsetfavorite";
        $postdata = 'id=' . $favoritelistid . '&name=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function updatefavoritelist($favoritelistid, $name) {
        $this->startTimer();
        $url = $this->server_url . "/core/updatefavoritelist";
        $postdata = 'id=' . $favoritelistid . '&name=' . $name;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function fileexists($filepath) {
        $this->startTimer();
        $url = $this->server_url . "/core/fileexists";
        $postdata = 'file=' . $filepath;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function fileinfo($filepath) {
        $this->startTimer();
        $url = $this->server_url . "/core/fileinfo";
        $postdata = 'file=' . $filepath;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "entry", "EntryRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function addShare($sharelocation, $sharename, $allowpublicaccess) {
        $url = $this->server_url . "/core/addshare";
        $postdata = 'sharelocation=' . $sharelocation . '&sharename=' . $sharename .
                '&allowpublicaccess=' . $allowpublicaccess;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "share", "ShareRecord");
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function clearFavoriteList($name) {
        $this->startTimer();
        $url = $this->server_url . "/core/clearfavoritesinnamedlist";
        $postdata = 'name=' . $name;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }
   
    //--- API for quickshare
    //--- RETURNS a share record
    public function Quickshare($sharelocation) {
        $this->startTimer();
        $url = $this->server_url . "/core/quickshare";
        $postdata = 'sharelocation=' . $sharelocation;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "share", "ShareRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //API for getfsthumbimage
    //---RETURNS a command Record
    public function getfsthumbimage($name, $height, $width) {
        $this->startTimer();
        $url = $this->server_url . '/core/getfsthumbimage?name=' . '/' . SERVER_USER . '/' . $name . '&height=' . $height . '&width=' . $width;
        $buffer = $this->doGET($url);
        $this->stopTimer();
        return $buffer;
    }
  
    
    //--- Download API
    public function downloadFile($path, $name, $savepath)
    {
	$url = $this->server_url.'/core/downloadfile?filepath='.$path.'&filename='.$name;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
        
   
    // API for verify email
    public function verifyEmail($username,$tag) {
        $this->startTimer();
        $url = $this->server_url . "/core/verifyemail?u=".$username.'&tag='.$tag;
        $buffer = $this->doPOST($url, '');
        $this->stopTimer();
        return $buffer;
    }
    
    // API for shorten (shorten longurl)
    public function shorten($longurl) {
        $this->startTimer();
        $url = $this->server_url . "/core/shorten?longurl=".$longurl;
        $buffer = $this->doPOST($url, '');
        $this->stopTimer();
        return $buffer;
    }
    
    public function getVerifyTag($profile){
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = 'op=getverifytag&profile='.$profile;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    public function get2FACode($profile , $token){
        $this->startTimer();        
        $url = $this->server_url . "/app/testhelper/";
        $postdata = 'op=get2facode&profile='.$profile . '&token='.$token;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
        
    public function getPassword($profile ){
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=getpassword&profile=".$profile ;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
            
    public function getFavoritesInNamedList($name){
        $this->startTimer();
        $url = $this->server_url . "/core/getfavoritesinnamedlist?name=".$name;
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer, "entry", "EntryRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;
     }
     public function getSystemStatus(){
        $this->startTimer();
        $url = $this->server_url . "/core/getsystemstatus";
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer, "status", "StatusRecord");
         if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        return NULL;
     }
     
     public function downloadFileMulti($path, $count, $filearray, $savepath ){
        $url = $this->server_url . '/core/downloadfilemulti?count=' . $count . '&filepath=' . $path;
        foreach ($filearray as $key => $value) {
            $url .= "&" . $key . "=" . $value;
        }
        $zipfile = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200'&& $zipfile != NULL) {
            $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$zipfile);			
            fclose($fp);
	    return true;
	}
	else {
            return false;
	}
     }
     
     public function search($location, $keyword = '', $minsizeinkb = '', $maxsizeinkb = ''){
        $this->startTimer();
        $url = $this->server_url . "/core/search";
        $postdata = 'location='.$location.'&keyword='.$keyword.'&minsize='.$minsizeinkb.'&maxsize='.$maxsizeinkb;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "entry", "EntryRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;
     }
     
     public function deletePartialUploads()
     {
         $this->startTimer();
         $url = $this->server_url . "/core/deletepartialuploads";
         $buffer = $this->doPOST($url, '');
         $collection = new Collection($buffer, "command", "CommandRecord");
         $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        return NULL;
    }
    
    public function getRecentId($ownername)
    {
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=getrecentid&owner=".$ownername ;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    
    //API emptyrecyclebin
    //Returns Command Record
    public function emptyRecycleBin()
     {
         $this->startTimer();
         $url = $this->server_url . "/app/explorer/emptyrecyclebin";
         $buffer = $this->doPOST($url, '');
         $collection = new Collection($buffer, "command", "CommandRecord");
         $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        return NULL;
    }
}

class CloudAdminAPI extends APICore
{
  
    public function __construct($SERVER_URL) {
        parent::__construct($SERVER_URL);
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function adminlogin($adminuser, $adminpassword) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=adminlogin&adminuser=' . $adminuser . '&adminpassword=' . $adminpassword;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    /**
     * Returns a list of User objects matching the specified criteria
     *
     * This method does a search of all users and returns users matching the specific pattern
     *
     * @param string $username
     * @return collection
     */
    public function searchUsers($username, $groupidnin="", $externalin="", $status="", $admin="") {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=search&keyword=' . $username . '&groupidnin=&externalin=&status=&statusnin=&start=0&end=10&admin=';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "user", "UserRecord", "meta");
        $this->stopTimer();
        return $collection;
    }
    
     //---- SETADMIN STATUS API
    //RETURNS a Command Record
    public function setAdminstatus($profile, $adminstatus) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setadminstatus&profile=' . $profile . '&adminstatus=' . $adminstatus;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---- ADDUSER API
    //RETURNS a Command Record
    public function addUser($username, $email, $password, $authtype) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=adduser&username=' . $username . '&email=' . $email . '&password=' . $password . '&authtype=' . $authtype;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---- DELETE USER API
    //RETURNS a Command Record
    public function deleteUser($profile) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deleteuser&profile=' . $profile;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    public function addgroup($groupname) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addgroup&groupname=' . $groupname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "group", "GroupRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
         {
         	$arr= $collection->getRecords();
 			return $arr[0];
        }
        else if($collection->getMetaRecord() != NULL)
        {
            return $collection;
        }
        else
        {
            return NULL;
        }
    }    

    public function deletegroup($groupId) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deletegroup&groupid=' . $groupId;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }
    
    public function addMemberToGroup($groupId, $userId) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addmembertogroup&groupid=' . $groupId . '&userid=' . $userId;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }
    
    public function deleteMemberFromGroup($groupId, $userId) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deletememberfromgroup&groupid=' . $groupId . '&userid=' . $userId;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }
    
     //API for TRIMAUDITDB
    //---RETURNS a command record
    public function trimAuditdb($enddate,$startdate = "") {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        if($startdate != "" && $startdate != NULL)
        {
            $postdata = 'op=trimauditdb&startdate=' . $startdate . '&enddate='.$enddate;
        }
        else
        {
            $postdata = 'op=trimauditdb&enddate=' . $enddate;
        }
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---SET ADMINUSERPOLICY API
    //---RETURNS a command record
    public function setadminUserpolicy($username, $opname, $create = "", $read = "", $update = "", $delete = "") {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        if ($create != "" && $create != null)
            $create = '&create=' . $create;

        if ($read != "" && $read != null)
            $read = '&read=' . $read;

        if ($update != "" && $update != null)
            $update = '&update=' . $update;

        if ($delete != "" && $delete != null)
            $delete = '&delete=' . $delete;

        $postdata = 'op=setadminuserpolicy&username=' . $username . '&opname=' . $opname . $create . $read . $update . $delete;
        ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //---API for CLEARALLALERTS
    //---RETURNS a command record
    public function clearallAlerts() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=clearallalerts';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }


    //API to activate encryption using a password
    //---RETURNS a command record
    public function cryptfsActivate($passphrase) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsactivate&passphrase=' . $passphrase;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //API to encrypt all the files
    //---RETURNS a command record
    public function cryptfsInit($passphrase, $addrecoverykey) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        //if ($passphrase != "" && $passphrase != null)
           // $passphrase = '&passphrase=' . $passphrase;
        $postdata = 'op=cryptfsinit&passphrase=' . $passphrase . '&addrecoverykey=' . $addrecoverykey;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //API to encrypt all the files
    //---RETURNS a command record
    public function cryptfsEncryptall() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsencryptall';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //API to decrypt all the files
    //---RETURNS a command record
    public function cryptfsDecryptall() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsdecryptall';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }

    //API to reset encryption
    //---RETURNS a command record
    public function cryptfsReset() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsreset';
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }

    //API to reset encryption
    //---RETURNS a encryption record
    public function cryptfsStatus() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsstatus';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "encstatus", "EncryptionstatusRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
	}
    
    public function cryptfsDownloadRecoveryKey(){
        $this->startTimer();
        $url = $this->server_url . "/admin/?op=cryptfsdownloadrecoverykey";
        $buffer = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
        if ($httpcode=='200' ) {
            
            return $buffer;
        }
        else {
            return false;
        }
 }
    
    public function logout() {
        $this->startTimer();
        $url = $this->server_url . "/admin/?op=logout";
        $postdata = "";
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
        }else{
        	return NULL;
        }
    }
    
    public function addExternal($externalname , $location, $automount, $automounttype, $automountparam1, $perm){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addexternal&externalname=' . $externalname . '&location='. $location . '&automount=' . $automount . '&automounttype='
                . $automounttype . '&automountparam1=' . $automountparam1 . '&perm=' . $perm  ;
        
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "external", "ExternalRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    public function updateExternal($externalid, $externalname , $location, $automount, $automounttype, $automountparam1, $perm){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updateexternal&externalid='. $externalid . '&externalname=' . $externalname . '&location='. $location . '&automount=' . $automount . '&automounttype='
                . $automounttype . '&automountparam1=' . $automountparam1 . '&perm=' . $perm  ;
        
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "external", "ExternalRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    public function addUsertoExternal($writemode ,$externalid ,$userid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addusertoexternal&writemode=' . $writemode .'&externalid='. $externalid . '&userid=' . $userid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        } 
        }
        
    public function getExternals($filter=''){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        if($filter != '')
        {
            $postdata = 'op=getexternals&filter='.$filter;
        }
        else
        {
            $postdata = 'op=getexternals';
        }
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "external", "ExternalRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }   
        }
        
    public function addGrouptoExternal($writemode ,$externalid ,$groupid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addgrouptoexternal&writemode=' . $writemode .'&externalid='. $externalid . '&groupid=' . $groupid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        } 
        }
        
    public function getGroupsForExternal($externalid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getgroupsforexternal&externalid='. $externalid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "group", "GroupListRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
        }
        
    public function getUsersForExternal($externalid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getusersforexternal&externalid='. $externalid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "user", "UserListRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
        }    
        
    public function deleteGroupFromExternal($externalid , $groupid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deletegroupfromexternal&externalid='. $externalid . '&groupid=' . $groupid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }   
        }
        
    public function deleteUserFromExternal($externalid , $username){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deleteuserfromexternal&externalid='. $externalid . '&userid=' . $username;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }
        
    public function deletExternal($externalid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deleteexternal&externalid='. $externalid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }  
        }
    
    
    public function updateUserAccessLevel($profilename , $emailid , $status){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updateuser&profile='. $profilename .'&email='. $emailid . '&status=' . $status;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }   
        }
    
    public function allowAccountSignUp($value){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setconfigsetting&count=1&param0=TONIDOCLOUD_ACCOUNT_CREATION_MODE&value0=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }   
        }
        
    public function set2fa($value){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setconfigsetting&count=1&param0=TONIDOCLOUD_ENABLE_2FA&value0=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        } 
        }    
 
    public function setConfigSettings($configconstant, $value){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setconfigsetting&count=1&param0='.$configconstant.'&value0=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }  
        }     
        
    public function getConfigSettings($configconstant){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getconfigsetting&count=1&param0='.$configconstant;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "setting", "ConfigSettingRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        } 
        } 
        
    //API to get config settings using an array
    //RETURNS a Command Record
    public function getConfigSettingsArray($count,$config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getconfigsetting&count='. $count;
        $i = 0;
         foreach ($config_array as $const) {
            $postdata .='&param'.$i."=" . $const ;
            $i++;
        }
        $buffer = $this->doPOST($url, $postdata);
        $pos = strpos($buffer, '0');
        if($pos == '56')
        {
            $collection = new Collection($buffer, "command", "CommandRecord");
            if ($collection->getNumberOfRecords() > 0)
            {
	        	$arr= $collection->getRecords();
				return $arr[0];
            }
        }
        
        $collection = new Collection($buffer, "setting", "ConfigSettingRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }   
        }    
        
    //API clearconfigsetting
    //RETURNS a Command Record
    public function clearConfigSetting($count,$config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=clearconfigsetting&count='. $count;
        $i = 0;
         foreach ($config_array as $const) {
            $postdata .='&param'.$i."=" . $const ;
            $i++;
        }
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }   
        }    
        

    public function updateBackupPath($profilename , $emailid , $backuppathoverride){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updateuser&profile='. $profilename .'&email='. $emailid . '&backuppathoverride=' . $backuppathoverride;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }
    //API to set config settings using an array
    //RETURNS a Command Record
    public function setConfigSettingsArray($count,$config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setconfigsetting&count='. $count;
        $i = 0;
         foreach ($config_array as $key => $value) {
            $postdata .='&param'.$i."=" . $key . '&value'.$i . "=".$value;
            $i++;
        }
        //echo $postdata;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }
    //API to check AD settings
    //RETURNS a Command Record
    public function checkAdLogin() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkadlogin';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }
        
    //API to check LDAP settings
    //RETURNS a Command Record
    public function checkLdapLogin() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkldaplogin';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }    
        
    //API to check storage settings
    //RETURNS a Command Record
    public function checkStorageSettingforLocal($storagetype, $path) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkstoragesetting&storagetype=' . $storagetype . '&path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }   
        
    public function checkStorageSettingforOpenStack($storagetype,$opserver, $opport , $opaccount , $opuser, $oppassword) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkstoragesetting&storagetype=' . $storagetype .'&server=' . $opserver . '&port=' . $opport . '&account=' . $opaccount . '&user=' . $opuser . '&password=' .$oppassword;;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }   
        
    public function checkStorageSettingforAmazonS3($storagetype,$key,$secret,$bucketid,$region,$endpoint,$noofversion) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkstoragesetting&storagetype=' . $storagetype . '&key=' . $key .'&secret=' .$secret . '&bucketid=' . 
                $bucketid . '&noov=' . $noofversion . '&region=' . $region . '&endpoint=' .$endpoint;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }    
        
    //API to check Clam AV settings
    //RETURNS a Command Record
    public function checkClamAV() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkclamav';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }    
        
    //API to check send email
    //RETURNS a Command Record
    public function checkSendEmail() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checksendemail';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }      
        
    //API to check setting path
    //RETURNS a Command Record
    public function checkSettingPath($path) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checksettingpath&path=' . $path ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }      
        
    //API to get email id for AD user
    //RETURNS a Command Record
    public function getEmailId($name) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getemailid&name=' . $name ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }
        
    //API to get email id for LDAP user
    //RETURNS a Command Record
    public function getEmailIdForLdap($name,$password) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getemailidforldap&name=' . $name . '&password=' .$password ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        }   
        
    public function getLicense(){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getlicense';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, 'license', 'LicenseRecord');
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
        
    }
        
    //API to get AD groups
    //RETURNS a Adgroup Record  
    public function getAdGroups(){
         $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadgroups';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "entry", "AdgroupRecord" , "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
        }
    //API to get Group by group name
    //RETURNS a Group Record
    public function getGroupByName($groupName) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getgroupbyname&groupname=' . $groupName;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "group", "GroupRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            {
	        	$arr= $collection->getRecords();
				return $arr[0];
            }
        else if($collection->getMetaRecord() != NULL)
            {
            return $collection;
            }
        else
            {
            return NULL;
            }
        }
    //API to update a group
    //RETURNS a Group Record
    public function updateGroup($groupName, $groupId) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updategroup&groupname=' . $groupName . '&groupid=' . $groupId;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "group", "GroupRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            {
	        	$arr= $collection->getRecords();
				return $arr[0];
            }
        else if($collection->getMetaRecord() != NULL)
            {
            return $collection;
            }
        else
            {
            return NULL;
            }
        }
        
    //API to get groups
    //RETURNS a Group  Record  
    public function getGroups(){
         $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getgroups';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "group", "GroupRecord" , "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
        }
       
    //API to get members of Group
    //RETURNS a Member Record
    public function getMembersForGroup() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkadlogin';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "member", "MembersRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }    
        
    //API to get admin users 
    //RETURNS AdminUsersRecord
    public function getAdminUsers() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadminusers';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "adminuser", "AdminUsersRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }    
     
    // API getadminuserpolicy
    // Returns UserOperationsRecord
    public function getAdminUserPolicy($username) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadminuserpolicy&username='.$username;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "operation", "UserOperationsRecord","meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }    
        
    // API getadminuseroperationpermission
    // Returns Permission Record
    public function getAdminUserOperationPermission($username,$opname) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadminuseroperationpermission&username='.$username.'&opname='.$opname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "permission", "PermissionRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
           	$arr= $collection->getRecords();
   			return $arr[0];
           }
        else
            {
            return NULL;
            }   
        }        
        
        
    // API getadminoperations
    // Returns UserOperationsRecord
    public function getAdminOperations() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadminoperations';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "operation", "UserOperationsRecord","meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }    
    // API deleteadminuser
    // Returns CommandRecord   
    public function deleteAdminUser($username) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deleteadminuser&username=' . $username;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            } 
    }    
    //API to get members of AD group
    //RETURNS a Entry Record
        public function getAdGroupMembers($gmgroup){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadgroupmembers&gmgroup=' . $gmgroup;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "entry", "AdgroupMemberRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;
    }


    // API getdoelist
    // Returns UserOperationsRecord
    public function getDoEList() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getdoelist';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "doeitem", "DoNotEmailRecord","meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }
        
    public function clearAllDoeList() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=clearalldoelist';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }    
    
    public function removeFromDoeList($rid) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=removefromdoelist&rid=' . $rid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    
    //Setuserpassword
    //Returns Command Record
    public function setUserPassword($username , $password) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setuserpassword&profile=' . $username . '&password=' . $password;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //Resetpassword
    //Returns Command Record
    public function resetPassword($username ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=resetpassword&profile=' . $username ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //GetSharesByOwner
    //Returns Share Record
    public function getSharesByOwner($ownername, $filter = "") {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getsharesbyowner&shareowner=' . $ownername .'&sharefilter=' .$filter;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "share", "ShareRecord", "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            {
                return $collection;
            }
        else
            {
                return $collection->getMetaRecord();
            }
    }
    
    //GetUser
    //Returns UserRecord
    public function getUser($username) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getuser&username=' . $username ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "user", "UserRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //GetUserUsage
    //Returns UserUsageRecord
    public function getUserUsage($username) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getuserusage&username=' . $username ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "usage", "UserUsageRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
        
        
    //API to get latest users added into system
    //RETURNS a ITEM Record
    public function getLatestUsersAdded() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getlatestusersadded';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "user", "UserRecord");
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API to get latest files added into system
    //RETURNS a ITEM Record
    public function getLatestFilesAdded() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getlatestfilesadded';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "item", "ItemRecord");
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
        	$arr= $collection->getRecords();
			return $arr[0];
		}
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API to generate test alerts
    //Return 1
    public function generateAlerts()
    {
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=generatealerts";
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }
    
    //API to create multi.php file
    //Return True/False
    public function createMultiSiteFile()
    {
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=createmultisitefile";
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }
    
    //API getsysalerts
    //Returns AlertsRecord
    public function getSysAlerts() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getsysalerts';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "alert", "AlertsRecord","meta");
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API removealert
    //Returns Command Record
    public function removeAlert($rid ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=removealert&rid=' . $rid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //API superadminaddsite
    //Returns Command Record
    public function superAdminAddSite($name , $siteurl , $duplicatesitename ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminaddsite&name=' . $name . '&url=' . $siteurl . '&duplicatesitename=' . $duplicatesitename;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //API superadmineditsite
    //Returns Command Record
    public function superAdminEditSite($name , $siteUrl ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadmineditsite&name=' . $name . '&url=' . $siteUrl ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //API superadminremovesite
    //Returns Command Record
    public function superAdminRemoveSite($siteurl ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminremovesite&url=' . $siteurl ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //API superadminlogin
    //Returns Command Record
    public function superAdminLogin($superadminuser , $superadminpassword ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminlogin&superadminuser=' . $superadminuser . '&superadminpassword=' . $superadminpassword ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //API superadmingetallsites
    //Returns AlertsRecord
    public function superAdminGetAllSites() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadmingetallsites';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "site", "SiteRecord","meta");
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API superadminlogout
    //Returns Command Record
    public function superAdminLogout( ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminlogout';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    //API to get config settings using an array
    //RETURNS a Command Record
    public function superAdminGetConfigSetting($count,$siteurl, $config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadmingetconfigsetting&count='. $count . '&sitehostname=' .$siteurl;
        $i = 0;
         foreach ($config_array as $const) {
            $postdata .='&param'.$i."=" . $const ;
            $i++;
        }
        $buffer = $this->doPOST($url, $postdata);
        $pos = strpos($buffer, '0');
        if($pos == '66')
        {
            $collection = new Collection($buffer, "command", "CommandRecord");
            if ($collection->getNumberOfRecords() > 0)
            {
	        	$arr= $collection->getRecords();
				return $arr[0];
            }
        }
        
        $collection = new Collection($buffer, "setting", "ConfigSettingRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        } 
        }
        
    //API to set config settings using an array
    //RETURNS a Command Record
    public function superAdminSetConfigSettings($count,$siteurl,$config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminsetconfigsetting&count='. $count. '&sitehostname=' .$siteurl;
        $i = 0;
         foreach ($config_array as $key => $value) {
            $postdata .='&param'.$i."=" . $key . '&value'.$i . "=".$value;
            $i++;
        }
        //echo $postdata;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }   
        }  
        
    //API superadminauthstatus
    //Returns Command Record
    public function superAdminAuthStatus( ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminauthstatus';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }

    
    //API getaudit
    //Returns AuditRecord
    public function getAudit($username = "" , $operation = "" , $startdate = "" , $enddate = "", $sortfield = "", $sortdir = "") {
        $this->startTimer();
        $url = $this->server_url . '/admin/?op=getaudit';
        if($username != "")
        {
            $url .= '&username=' .  $username;
        }
        if($operation != "")
        {
            $url .= '&operation=' .$operation;
        }
        if($startdate != "")
        {
            $url .= '&startdate=' .$startdate; 
        }
        if($enddate != "")
        {
            $url .= '&enddate='.$enddate;
        }
        if($sortfield != "")
        {
            $url .= '&sortfield=' . $sortfield;
        }
        if($sortdir != "")
        {
            $url .= '&sortdir=' . $sortdir;
        }
        $buffer = $this->doGET($url);
        $collection = new Collection($buffer, "log", "AuditRecord","meta");
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API exportAudit
    //Returns csv file
    public function exportAudit($enddate , $savepath , $startdate = "" )
    {
        $this->startTimer();
        if($startdate != "" && $startdate != NULL)
        {
            $url = $this->server_url . '/admin/index.php/?op=exportaudit&startdate=' .$startdate . '&enddate='.$enddate;
        }
        else
        {
            $url = $this->server_url . '/admin/index.php/?op=exportaudit&enddate='.$enddate;
        }
        $buffer = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$buffer);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    //API generateffdc
    //Return a zip file
    public function generateFFDC($savepath)
    {
        $url = $this->server_url . '/admin/index.php/?op=generateffdc';
        $zipfile = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200'&& $zipfile != NULL) {
            $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$zipfile);			
            fclose($fp);
	    return true;
	}
	else {
            return false;
	}
    }
    
     //API to update user
    //Returns a Command Record
    public function updateUser($profile, $size, $status, $verified, $email, $localuser, $displayname, 
            $expirationdate, $adminstatus, $sharemode, $disablemyfilessync, $disablenetworksync, $backuppathoverride ){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updateuser&profile=' . $profile . '&size=' . $size .
                '&status=' . $status  . '&verified=' . $verified  . '&email=' . $email . '&localuser=' . $localuser .   
                '&displayname=' . $displayname . '&expirationdate=' . $expirationdate .   
                '&adminstatus=' . $adminstatus . '&sharemode=' . $sharemode .
                '&disablemyfilessync=' . $disablemyfilessync . 
                '&disablenetworksync=' . $disablenetworksync . '&backuppathoverride=' . $backuppathoverride;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    //API to importadgroup
    //Returns a groupimport Record
    public function importAdGroup($groupname, $groupid, $autosync){
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=importadgroup&groupname=' . $groupname . '&groupid=' . $groupid .
                '&autosync=' . $autosync;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "groupimport", "AdGroupImportRecord");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0){
        	$arr= $collection->getRecords();
			return $arr[0];
		}
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    public function twofaAdminLogin($adminuser, $token, $code)
    {
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=2falogin&adminuser=' . $adminuser . '&token=' . $token . '&code=' .$code;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
    
    public function twofaAdminCode($issuperadmin)
    {
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=twofaadmincode&issuperadmin=".$issuperadmin ;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    public function clearAllConfigSetting()
    {
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=clearallconfigsetting';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "command", "CommandRecord");
        if ($collection->getNumberOfRecords() > 0)
        {
           
        	$arr= $collection->getRecords();
			return $arr[0];
        }
        else
        {
            return NULL;
        }
    }
     
}   
    
?>